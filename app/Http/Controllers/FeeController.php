<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSeatBookingRequest;
use App\Http\Requests\UpdateFeeRequest;
use App\Models\FeeInstallment;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\FeeService;
use App\Services\LibraryScheduleService;
use App\Services\PlanExpiryService;
use App\Services\SeatConflictService;
use App\Services\SeatMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(Request $request, FeeService $feeService): View
    {
        $rows = $feeService->baseQuery($this->optionalActiveBranchId($request))
            ->orderByDesc('id')
            ->get()
            ->map(fn ($booking) => $feeService->serializeRow($booking));

        $students = $this->constrainByActiveBranch(Student::query(), $request)
            ->where('status', 'active')
            ->with(['bookings' => function ($query) {
                $query->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->with(['seat.hall.branch', 'installments'])
                    ->latest('id');
            }])
            ->orderBy('name')
            ->get()
            ->map(fn (Student $student) => $this->serializeStudentForFeeForm($student));

        $halls = $this->constrainByActiveBranch(Hall::query()->with('branch'), $request)
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $scheduleBranch = $this->optionalActiveBranch($request) ?? $halls->first()?->branch;
        $schedule = $scheduleBranch
            ? LibraryScheduleService::forBranch($scheduleBranch)
            : null;

        return view('fees.index', [
            'rows' => $rows,
            'students' => $students,
            'halls' => $halls,
            'timeSlotOptions' => $schedule?->timeSlotOptions() ?? LibraryScheduleService::defaultOptions(),
            'scopeLabel' => $this->viewingAllBranches($request)
                ? 'all branches'
                : ($this->optionalActiveBranch($request)?->name ?? ''),
        ]);
    }

    public function store(
        StoreSeatBookingRequest $request,
        PlanExpiryService $planExpiryService,
        SeatMapService $seatMapService,
        FeeService $feeService,
    ): JsonResponse {
        $seat = Seat::query()->with('hall.branch')->where('id', $request->integer('seat_id'))->firstOrFail();
        $this->assertCanAccessBranch($request, $seat->hall?->branch_id);
        abort_unless($request->integer('hall_id') === $seat->hall_id, 422, 'Seat does not belong to selected hall.');

        $student = Student::query()
            ->where('id', $request->integer('student_id'))
            ->where('branch_id', $seat->hall->branch_id)
            ->firstOrFail();

        if ($student->student_type !== Student::TYPE_REGULAR) {
            $student->update(['student_type' => Student::TYPE_REGULAR]);
        }

        $existing = SeatBooking::query()
            ->with(['seat.hall.branch', 'installments'])
            ->where('student_id', $student->id)
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->first();

        if ($existing) {
            $joining = Carbon::parse($request->input('joining_date') ?: $existing->joining_date);
            $feeType = $feeService->normalizeFeeType((string) $request->input('fee_type', $existing->fee_type));
            $expiry = $feeService->resolveExpiry(
                $feeType,
                $joining,
                $request->filled('plan_expiry_date') ? Carbon::parse($request->input('plan_expiry_date')) : $existing->plan_expiry_date,
            );
            $branch = $existing->seat?->hall?->branch ?? $seat->hall->branch;

            if ($branch && SeatConflictService::forBranch($branch)->hasConflict(
                $existing->seat_id,
                $existing->time_slot,
                $joining,
                $expiry,
                $existing->custom_start_time ? substr((string) $existing->custom_start_time, 0, 5) : null,
                $existing->custom_end_time ? substr((string) $existing->custom_end_time, 0, 5) : null,
                $existing->id,
            )) {
                return response()->json(['message' => 'Those dates overlap another booking on this seat.'], 422);
            }

            $booking = $this->applyFeeDetails($request, $existing, $feeService);

            if ($branch) {
                $seatMapService->broadcastForBranch((int) $branch->id);
            }

            return response()->json([
                'message' => 'Fee saved for the assigned seat.',
                'row' => $feeService->serializeRow($booking->fresh(['student', 'seat.hall.branch', 'installments'])),
            ]);
        }

        abort_unless((float) $request->input('fee_amount') > 0, 422, 'Amount must be greater than 0.');

        $joining = Carbon::parse($request->input('joining_date'));
        $feeType = $feeService->normalizeFeeType((string) $request->input('fee_type'));
        $expiry = $feeService->resolveExpiry(
            $feeType,
            $joining,
            $request->filled('plan_expiry_date') ? Carbon::parse($request->input('plan_expiry_date')) : null,
        );

        $conflictService = SeatConflictService::forBranch($seat->hall->branch);

        if ($conflictService->hasConflict(
            $seat->id,
            $request->input('time_slot'),
            $joining,
            $expiry,
            $request->input('custom_start_time'),
            $request->input('custom_end_time'),
        )) {
            return response()->json(['message' => 'This seat is already taken for those dates and hours.'], 422);
        }

        $booking = SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => $request->input('time_slot'),
            'custom_start_time' => $request->input('custom_start_time'),
            'custom_end_time' => $request->input('custom_end_time'),
            'fee_type' => $feeType,
            'payment_plan' => $feeService->normalizePaymentPlan($request->input('payment_plan'), (string) $request->input('fee_type')),
            'installment_frequency' => $request->input('installment_frequency'),
            'fee_amount' => round((float) ($request->input('fee_amount') ?? 0), 2),
            'amount_paid' => 0,
            'membership_mode' => $request->input('membership_mode'),
            'joining_date' => $joining,
            'plan_expiry_date' => $expiry,
            'status' => 'occupied',
        ]);

        $this->logActivity($request, 'fees.created', "Added a fee for {$student->name}.", $booking, $seat->hall->branch_id);

        $booking = $this->applyFeeDetails($request, $booking, $feeService);

        $seatMapService->broadcastForBranch((int) $seat->hall->branch_id);

        return response()->json([
            'message' => 'Fee added.',
            'row' => $feeService->serializeRow($booking->fresh(['student', 'seat.hall.branch', 'installments'])),
        ], 201);
    }

    public function show(Request $request, SeatBooking $booking, FeeService $feeService): JsonResponse
    {
        $booking->load(['student', 'seat.hall.branch']);
        $this->assertCanAccessBranch($request, $booking->seat?->hall?->branch_id);

        return response()->json($feeService->serializeRow($booking));
    }

    public function update(
        UpdateFeeRequest $request,
        SeatBooking $booking,
        SeatMapService $seatMapService,
        FeeService $feeService,
    ): JsonResponse {
        $booking->load(['student', 'seat.hall.branch']);
        $this->assertCanAccessBranch($request, $booking->seat?->hall?->branch_id);

        $joining = Carbon::parse($request->input('joining_date'));
        $feeType = $feeService->normalizeFeeType((string) $request->input('fee_type'));
        $expiry = $feeService->resolveExpiry(
            $feeType,
            $joining,
            $request->filled('plan_expiry_date') ? Carbon::parse($request->input('plan_expiry_date')) : null,
        );
        $branch = $booking->seat?->hall?->branch;

        if ($branch && SeatConflictService::forBranch($branch)->hasConflict(
            $booking->seat_id,
            $booking->time_slot,
            $joining,
            $expiry,
            $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null,
            $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null,
            $booking->id,
        )) {
            return response()->json(['message' => 'Those dates overlap another booking on this seat.'], 422);
        }

        $booking = $this->applyFeeDetails($request, $booking, $feeService);

        if ($branch) {
            $seatMapService->broadcastForBranch((int) $branch->id);
        }

        return response()->json([
            'message' => 'Fee updated.',
            'row' => $feeService->serializeRow($booking->fresh(['student', 'seat.hall.branch', 'installments'])),
        ]);
    }

    public function markPaid(Request $request, SeatBooking $booking, FeeService $feeService): JsonResponse
    {
        $booking->load('seat.hall');
        $this->assertCanAccessBranch($request, $booking->seat?->hall?->branch_id);

        $booking = $feeService->markPaid($booking);

        return response()->json([
            'message' => 'Fee marked as paid.',
            'row' => $feeService->serializeRow($booking->fresh(['student', 'seat.hall.branch', 'installments'])),
        ]);
    }

    public function recordPayment(Request $request, SeatBooking $booking, FeeService $feeService): JsonResponse
    {
        $booking->load(['student', 'seat.hall', 'installments']);
        $this->assertCanAccessBranch($request, $booking->seat?->hall?->branch_id);

        $validated = $request->validate([
            'fee_amount' => ['nullable', 'numeric', 'min:0.01'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'in:cash,upi,card,bank_transfer,other'],
            'payment_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        if ((float) ($booking->fee_amount ?? 0) <= 0) {
            $feeAmount = round((float) ($validated['fee_amount'] ?? 0), 2);
            abort_if($feeAmount <= 0, 422, 'Set the total fee amount before recording a payment.');
            $booking->update(['fee_amount' => $feeAmount]);
            $booking->refresh();
        }

        $due = $feeService->amountDue($booking);
        abort_if($due <= 0, 422, 'This fee is already fully paid.');

        $amount = round((float) $validated['amount'], 2);
        abort_if($amount > $due + 0.009, 422, 'Amount cannot exceed the remaining due (₹'.$due.').');

        $booking = $feeService->recordPayment($booking, $amount, [
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['note'] ?? null,
            'received_by' => $request->user()?->id,
        ]);

        $note = trim((string) ($validated['note'] ?? ''));
        $description = "Received ₹{$amount} for {$booking->student?->name}.";
        if ($note !== '') {
            $description .= " Note: {$note}";
        }

        $this->logActivity(
            $request,
            'fees.payment_received',
            $description,
            $booking,
            $booking->seat?->hall?->branch_id,
            [
                'amount' => $amount,
                'note' => $note !== '' ? $note : null,
                'amount_paid' => $booking->amount_paid,
                'amount_due' => $feeService->amountDue($booking),
            ],
        );

        return response()->json([
            'message' => 'Payment recorded.',
            'row' => $feeService->serializeRow($booking->fresh(['student', 'seat.hall.branch', 'installments'])),
        ]);
    }

    public function payInstallment(Request $request, SeatBooking $booking, FeeInstallment $installment, FeeService $feeService): JsonResponse
    {
        $booking->load('seat.hall');
        $this->assertCanAccessBranch($request, $booking->seat?->hall?->branch_id);
        abort_unless((int) $installment->seat_booking_id === (int) $booking->id, 404);

        $feeService->markInstallmentPaid($installment);

        return response()->json([
            'message' => 'Installment marked as paid.',
            'row' => $feeService->serializeRow($booking->fresh(['student', 'seat.hall.branch', 'installments'])),
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $bookings = $this->constrainByActiveSeatHall(SeatBooking::query(), $request)
            ->whereIn('id', $validated['ids'])
            ->whereNull('cancelled_at')
            ->get();

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }

        $this->logActivity($request, 'fees.bulk_deleted', 'Removed '.$bookings->count().' fee record(s).');

        return response()->json([
            'message' => $bookings->count().' fee record(s) removed.',
            'deleted' => $bookings->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStudentForFeeForm(Student $student): array
    {
        $booking = $student->bookings->first();

        return [
            'id' => $student->id,
            'student_code' => $student->student_code,
            'name' => $student->name,
            'phone' => $student->phone,
            'student_type' => $student->student_type,
            'current_assignment' => $booking ? $this->serializeAssignmentForFeeForm($booking) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAssignmentForFeeForm(SeatBooking $booking): array
    {
        $start = $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null;
        $end = $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null;
        $branch = $booking->seat?->hall?->branch;
        $label = $branch
            ? LibraryScheduleService::forBranch($branch)->slotLabel($booking->time_slot, $start, $end)
            : str_replace('_', ' ', $booking->time_slot ?? '');

        return [
            'id' => $booking->id,
            'hall_id' => $booking->seat?->hall_id,
            'hall_name' => $booking->seat?->hall?->name,
            'seat_id' => $booking->seat_id,
            'seat_number' => $booking->seat?->seat_number,
            'time_slot' => $booking->time_slot,
            'time_slot_label' => $label,
            'custom_start_time' => $start ?: '09:00',
            'custom_end_time' => $end ?: '18:00',
            'joining_date' => $booking->joining_date?->toDateString(),
            'plan_expiry_date' => $booking->plan_expiry_date?->toDateString(),
            'fee_type' => app(FeeService::class)->normalizeFeeType((string) $booking->fee_type),
            'fee_amount' => $booking->fee_amount,
            'membership_mode' => $booking->membership_mode,
            'payment_plan' => app(FeeService::class)->normalizePaymentPlan($booking->payment_plan, (string) $booking->fee_type),
            'installment_count' => $booking->installments->count() ?: null,
            'installment_frequency' => app(FeeService::class)->normalizeFrequency($booking->installment_frequency),
            'first_due_date' => $booking->installments->first()?->due_date?->toDateString() ?: $booking->joining_date?->toDateString(),
        ];
    }

    private function applyFeeDetails(Request $request, SeatBooking $booking, FeeService $feeService): SeatBooking
    {
        $joining = Carbon::parse($request->input('joining_date') ?: $booking->joining_date);
        $feeType = $feeService->normalizeFeeType((string) $request->input('fee_type', $booking->fee_type));
        $paymentPlan = $feeService->normalizePaymentPlan($request->input('payment_plan'), (string) $request->input('fee_type', $booking->fee_type));
        $expiry = $feeService->resolveExpiry(
            $feeType,
            $joining,
            $request->filled('plan_expiry_date') ? Carbon::parse($request->input('plan_expiry_date')) : $booking->plan_expiry_date,
        );

        $booking->fill([
            'fee_type' => $feeType,
            'payment_plan' => $paymentPlan,
            'installment_frequency' => $paymentPlan === 'installments'
                ? $feeService->normalizeFrequency((string) $request->input('installment_frequency', 'monthly'))
                : null,
            'fee_amount' => $request->input('fee_amount', $booking->fee_amount),
            'amount_paid' => $booking->amount_paid ?? 0,
            'joining_date' => $joining,
            'plan_expiry_date' => $expiry,
            'membership_mode' => $request->input('membership_mode', $booking->membership_mode),
            'expiry_reminder_sent_at' => null,
        ])->save();

        $booking = $booking->fresh('installments');

        if ($paymentPlan === 'installments') {
            $frequency = $feeService->normalizeFrequency((string) $request->input('installment_frequency', 'monthly'));
            $firstDue = $request->filled('first_due_date')
                ? Carbon::parse($request->input('first_due_date'))
                : ($booking->joining_date?->copy() ?? Carbon::today());

            if ($frequency === 'custom') {
                $feeService->syncInstallments($booking, 0, 'custom', $firstDue);
            } else {
                $count = $request->filled('installment_count')
                    ? (int) $request->integer('installment_count')
                    : $feeService->suggestedInstallmentCount($firstDue, $expiry, $frequency);

                $feeService->syncInstallments($booking, max(2, $count), $frequency, $firstDue);
            }
        } else {
            $booking->installments()->whereNull('paid_at')->delete();
        }

        return $booking->fresh('installments');
    }
}
