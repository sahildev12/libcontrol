<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSeatBookingRequest;
use App\Http\Requests\TransferSeatBookingRequest;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\FeeService;
use App\Services\PlanExpiryService;
use App\Services\SeatConflictService;
use App\Services\SeatMapService;
use App\Services\LibraryScheduleService;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SeatBookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = $this->constrainByActiveSeatHall(SeatBooking::query(), $request)
            ->with(['student', 'seat.hall.branch'])
            ->whereNull('cancelled_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SeatBooking $booking) => $this->serializeBooking($booking));

        $students = $this->constrainByActiveBranch(Student::query(), $request)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'student_code', 'name', 'phone', 'student_type']);

        $halls = $this->constrainByActiveBranch(Hall::query()->with('branch'), $request)
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $scheduleBranch = $this->optionalActiveBranch($request) ?? $halls->first()?->branch;
        $schedule = $scheduleBranch
            ? LibraryScheduleService::forBranch($scheduleBranch)
            : null;

        return view('seat-assignments.index', [
            'bookings' => $bookings,
            'students' => $students,
            'halls' => $halls,
            'timeSlotOptions' => $schedule?->timeSlotOptions() ?? LibraryScheduleService::defaultOptions(),
        ]);
    }

    public function availableSeats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'time_slot' => ['required', 'in:full_day,custom_hours'],
            'joining_date' => ['required', 'date'],
            'plan_expiry_date' => ['required', 'date'],
            'custom_start_time' => ['nullable', 'date_format:H:i'],
            'custom_end_time' => ['nullable', 'date_format:H:i'],
            'ignore_booking_id' => ['nullable', 'integer', 'exists:seat_bookings,id'],
            'exclude_seat_id' => ['nullable', 'integer', 'exists:seats,id'],
            'include_unavailable' => ['nullable', 'boolean'],
        ]);

        $hall = Hall::query()->with('branch')->where('id', $validated['hall_id'])->firstOrFail();
        $this->assertCanAccessBranch($request, $hall->branch_id);

        $branch = $hall->branch;
        $conflictService = SeatConflictService::forBranch($branch);

        $joining = Carbon::parse($validated['joining_date']);
        $expiry = Carbon::parse($validated['plan_expiry_date']);
        $ignoreBookingId = isset($validated['ignore_booking_id']) ? (int) $validated['ignore_booking_id'] : null;
        $excludeSeatId = isset($validated['exclude_seat_id']) ? (int) $validated['exclude_seat_id'] : null;
        $includeUnavailable = (bool) ($validated['include_unavailable'] ?? false);

        $availability = SeatAvailabilityService::forBranch($branch);
        $schedule = LibraryScheduleService::forBranch($branch);

        $seats = Seat::query()
            ->with(['bookings' => function ($query) {
                $query->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->with('student:id,name,student_code');
            }])
            ->where('hall_id', $validated['hall_id'])
            ->when($excludeSeatId, fn ($query) => $query->where('id', '!=', $excludeSeatId))
            ->orderByRaw('CAST(seat_number AS UNSIGNED)')
            ->orderBy('seat_number')
            ->get()
            ->map(function (Seat $seat) use ($conflictService, $validated, $joining, $expiry, $ignoreBookingId, $availability, $schedule) {
                $hasConflict = $conflictService->hasConflict(
                    $seat->id,
                    $validated['time_slot'],
                    $joining,
                    $expiry,
                    $validated['custom_start_time'] ?? null,
                    $validated['custom_end_time'] ?? null,
                    $ignoreBookingId,
                );

                $activeBookings = $seat->bookings
                    ->filter(function (SeatBooking $booking) use ($ignoreBookingId) {
                        return ! $ignoreBookingId || (int) $booking->id !== $ignoreBookingId;
                    })
                    ->values();

                $fullDayBooked = $activeBookings->contains(fn (SeatBooking $booking) => $booking->time_slot === 'full_day');

                return [
                    'id' => $seat->id,
                    'seat_number' => $seat->seat_number,
                    'available' => ! $hasConflict,
                    'full_day_booked' => $fullDayBooked,
                    'today_windows' => $availability->availabilityTimeline($seat),
                    'bookings' => $activeBookings->map(function (SeatBooking $booking) use ($schedule) {
                        $start = $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null;
                        $end = $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null;

                        return [
                            'id' => $booking->id,
                            'student_name' => $booking->student?->name,
                            'student_code' => $booking->student?->student_code,
                            'time_slot' => $booking->time_slot,
                            'time_slot_label' => $schedule->slotLabel($booking->time_slot, $start, $end),
                            'custom_start_time' => $start,
                            'custom_end_time' => $end,
                        ];
                    })->all(),
                ];
            })
            ->when(! $includeUnavailable, fn ($collection) => $collection->filter(fn (array $seat) => $seat['available']))
            ->values();

        return response()->json(['seats' => $seats]);
    }

    public function transfer(
        TransferSeatBookingRequest $request,
        SeatMapService $seatMapService,
    ): JsonResponse {
        $oldBooking = SeatBooking::query()
            ->with(['student', 'seat.hall.branch', 'installments'])
            ->where('id', $request->integer('booking_id'))
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->firstOrFail();

        $this->assertCanAccessBranch($request, $oldBooking->seat?->hall?->branch_id);

        $newSeat = Seat::query()->with('hall.branch')->where('id', $request->integer('seat_id'))->firstOrFail();
        $this->assertCanAccessBranch($request, $newSeat->hall?->branch_id);
        abort_unless($request->integer('hall_id') === $newSeat->hall_id, 422, 'Seat does not belong to selected hall.');
        abort_unless((int) $oldBooking->seat_id > 0, 422, 'Current assignment is invalid.');
        abort_if((int) $newSeat->id === (int) $oldBooking->seat_id, 422, 'Choose a different seat than the current one.');

        $duplicateActive = SeatBooking::query()
            ->where('student_id', $oldBooking->student_id)
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $oldBooking->id)
            ->exists();

        abort_if($duplicateActive, 422, 'This student already has another active seat assignment.');

        $joining = $oldBooking->joining_date?->copy() ?? Carbon::today();
        $expiry = $oldBooking->plan_expiry_date?->copy() ?? $joining->copy();
        $timeSlot = (string) $request->input('time_slot');
        $customStart = $request->input('custom_start_time');
        $customEnd = $request->input('custom_end_time');

        if ($timeSlot === 'custom_hours') {
            abort_unless($customStart && $customEnd, 422, 'Start and end time are required for custom hours.');
            abort_if($customStart >= $customEnd, 422, 'End time must be after start time.');
        }

        $conflictService = SeatConflictService::forBranch($newSeat->hall->branch);

        try {
            $newBooking = DB::transaction(function () use (
                $oldBooking,
                $newSeat,
                $timeSlot,
                $customStart,
                $customEnd,
                $joining,
                $expiry,
                $conflictService,
            ) {
                $lockedOld = SeatBooking::query()
                    ->whereKey($oldBooking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_if($lockedOld->cancelled_at !== null || $lockedOld->status === 'cancelled', 422, 'This assignment is no longer active.');

                if ($conflictService->hasConflict(
                    $newSeat->id,
                    $timeSlot,
                    $joining,
                    $expiry,
                    $customStart,
                    $customEnd,
                    $lockedOld->id,
                )) {
                    throw new \RuntimeException('That seat/time is no longer available. Another booking may have been made. Refresh and try again.');
                }

                $newSeatNumber = $newSeat->seat_number;

                $lockedOld->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => "Transferred to seat {$newSeatNumber}",
                ]);

                $created = SeatBooking::query()->create([
                    'seat_id' => $newSeat->id,
                    'student_id' => $lockedOld->student_id,
                    'time_slot' => $timeSlot,
                    'custom_start_time' => $timeSlot === 'custom_hours' ? $customStart : null,
                    'custom_end_time' => $timeSlot === 'custom_hours' ? $customEnd : null,
                    'fee_type' => $lockedOld->fee_type,
                    'payment_plan' => $lockedOld->payment_plan,
                    'installment_frequency' => $lockedOld->installment_frequency,
                    'fee_amount' => $lockedOld->fee_amount,
                    'amount_paid' => $lockedOld->amount_paid,
                    'fee_paid_at' => $lockedOld->fee_paid_at,
                    'membership_mode' => $lockedOld->membership_mode,
                    'joining_date' => $joining,
                    'plan_expiry_date' => $expiry,
                    'status' => 'occupied',
                    'trial_start' => $lockedOld->trial_start,
                    'trial_end' => $lockedOld->trial_end,
                    'expiry_reminder_sent_at' => $lockedOld->expiry_reminder_sent_at,
                ]);

                $lockedOld->installments()->update(['seat_booking_id' => $created->id]);

                return $created->fresh(['student', 'seat.hall.branch', 'installments']);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $branchId = $newSeat->hall->branch_id;
        $seatMapService->broadcastForBranch((int) $branchId);
        if ($oldBooking->seat?->hall?->branch_id && (int) $oldBooking->seat->hall->branch_id !== (int) $branchId) {
            $seatMapService->broadcastForBranch((int) $oldBooking->seat->hall->branch_id);
        }

        $studentName = $newBooking->student?->name ?? 'Student';
        $this->logActivity(
            $request,
            'seat.transferred',
            "Transferred {$studentName} to seat {$newSeat->seat_number}.",
            $newBooking,
            $branchId,
            [
                'from_booking_id' => $oldBooking->id,
                'to_booking_id' => $newBooking->id,
                'from_seat_id' => $oldBooking->seat_id,
                'to_seat_id' => $newSeat->id,
            ],
        );

        return response()->json([
            'message' => "Transfer successful! {$studentName} moved to Seat {$newSeat->seat_number} ({$newSeat->hall?->name}).",
            'booking' => $this->serializeBooking($newBooking, true),
            'closed_booking_id' => $oldBooking->id,
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

        $branch = $seat->hall->branch;
        $conflictService = SeatConflictService::forBranch($branch);
        $schedule = LibraryScheduleService::forBranch($branch);

        $student = Student::query()
            ->where('id', $request->integer('student_id'))
            ->where('branch_id', $seat->hall->branch_id)
            ->firstOrFail();

        // Keep trial students as trial so the seat map shows Trial (blue).
        // Regular fee assignments still create an occupied booking record.

        $joining = Carbon::parse($request->input('joining_date'));
        $feeType = $planExpiryService->normalize($request->input('fee_type'));
        $expiry = $request->filled('plan_expiry_date')
            ? Carbon::parse($request->input('plan_expiry_date'))
            : $planExpiryService->calculate($feeType, $joining);

        $timeSlot = (string) $request->input('time_slot');
        $customStart = $request->input('custom_start_time');
        $customEnd = $request->input('custom_end_time');

        if ($timeSlot === 'custom_hours') {
            [$startMinutes, $endMinutes] = $schedule->slotWindow('custom_hours', $customStart, $customEnd);
            $customStart = sprintf('%02d:%02d', intdiv($startMinutes, 60) % 24, $startMinutes % 60);
            $customEnd = $endMinutes >= 24 * 60
                ? '23:59'
                : sprintf('%02d:%02d', intdiv($endMinutes, 60) % 24, $endMinutes % 60);
        }

        if ($conflictService->hasConflict(
            $seat->id,
            $timeSlot,
            $joining,
            $expiry,
            $customStart,
            $customEnd,
        )) {
            return response()->json(['message' => 'This seat has a conflicting assignment for the selected time slot and dates.'], 422);
        }

        $paymentPlan = $feeService->normalizePaymentPlan($request->input('payment_plan'), (string) $request->input('fee_type'));
        $isTrialStudent = $student->student_type === Student::TYPE_TRIAL;
        $amountReceived = round((float) $request->input('amount_received', 0), 2);

        $booking = SeatBooking::create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => $timeSlot,
            'custom_start_time' => $timeSlot === 'custom_hours' ? $customStart : null,
            'custom_end_time' => $timeSlot === 'custom_hours' ? $customEnd : null,
            'fee_type' => $feeType,
            'payment_plan' => $paymentPlan,
            // Installments are flexible (pay as received) — no fixed schedule from seats assign.
            'installment_frequency' => $paymentPlan === 'installments' ? 'custom' : null,
            'fee_amount' => round((float) ($request->input('fee_amount') ?? 0), 2),
            'amount_paid' => 0,
            'membership_mode' => $request->input('membership_mode'),
            'joining_date' => $joining,
            'plan_expiry_date' => $expiry,
            'status' => $isTrialStudent ? 'on_trial' : 'occupied',
            'trial_start' => $isTrialStudent ? $joining : null,
            'trial_end' => $isTrialStudent ? $expiry : null,
        ]);

        if ($amountReceived > 0) {
            $feeService->recordPayment($booking, $amountReceived, [
                'payment_method' => $request->input('payment_method', 'cash'),
                'payment_date' => $request->input('payment_date') ?: now()->toDateString(),
                'reference' => $request->input('payment_reference'),
                'notes' => $request->input('payment_notes'),
                'received_by' => $request->user()?->id,
            ]);
        }

        $booking->load(['student:id,student_code,name,student_type', 'seat.hall:id,name', 'payments']);
        $seatMapService->broadcastForBranch($seat->hall->branch_id);
        $this->logActivity(
            $request,
            'seat.assigned',
            "Assigned seat {$seat->seat_number} to {$student->name}.",
            $booking,
            $seat->hall->branch_id,
        );

        return response()->json([
            'message' => 'Seat assigned successfully.',
            'booking' => $this->serializeBooking($booking->fresh(['student', 'seat.hall', 'payments'])),
        ], 201);
    }

    public function cancel(Request $request, SeatBooking $booking, SeatMapService $seatMapService): JsonResponse
    {
        $this->authorizeBooking($request, $booking);

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
        ]);

        $branchId = $booking->seat?->hall?->branch_id;
        if ($branchId) {
            $seatMapService->broadcastForBranch($branchId);
        }
        $this->logActivity($request, 'seat.cancelled', 'Cancelled a seat assignment.', $booking, $branchId);

        return response()->json(['message' => 'Seat assignment cancelled.']);
    }

    public function convertToRegular(Request $request, SeatBooking $booking, SeatMapService $seatMapService): JsonResponse
    {
        $this->authorizeBooking($request, $booking);
        abort_if($booking->cancelled_at !== null, 422, 'This assignment is no longer active.');

        $booking->load('student');
        $isTrial = $booking->status === 'on_trial'
            || $booking->trial_end !== null
            || $booking->student?->student_type === Student::TYPE_TRIAL;

        abort_unless($isTrial, 422, 'Only trial bookings can be converted to regular.');

        if ($booking->student && $booking->student->student_type !== Student::TYPE_REGULAR) {
            $booking->student->update(['student_type' => Student::TYPE_REGULAR]);
        }

        $booking->update([
            'status' => 'occupied',
            'trial_start' => null,
            'trial_end' => null,
        ]);

        $branchId = $booking->seat?->hall?->branch_id;
        if ($branchId) {
            $seatMapService->broadcastForBranch($branchId);
        }

        $this->logActivity(
            $request,
            'seat.converted_to_regular',
            "Converted trial booking for {$booking->student?->name} to regular.",
            $booking->fresh('student'),
            $branchId,
        );

        return response()->json([
            'message' => 'Trial student converted to regular.',
            'booking' => $this->serializeBooking($booking->fresh(['student', 'seat.hall']), true),
        ]);
    }

    public function show(Request $request, SeatBooking $booking): JsonResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_if($booking->cancelled_at !== null, 404);

        $booking->load(['student', 'seat.hall.branch', 'seat.bookings.student']);

        return response()->json($this->serializeBooking($booking, true));
    }

    public function bulkCancel(Request $request, SeatMapService $seatMapService): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $bookings = SeatBooking::query()
            ->with('seat.hall')
            ->whereIn('id', $validated['ids'])
            ->whereNull('cancelled_at')
            ->get()
            ->filter(function (SeatBooking $booking) use ($request) {
                try {
                    $this->assertCanAccessBranch($request, $booking->seat?->hall?->branch_id);

                    return true;
                } catch (\Throwable) {
                    return false;
                }
            });

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }

        foreach ($bookings->map(fn (SeatBooking $booking) => $booking->seat?->hall?->branch_id)->unique()->filter() as $broadcastBranchId) {
            $seatMapService->broadcastForBranch((int) $broadcastBranchId);
        }

        return response()->json([
            'message' => "{$bookings->count()} assignment(s) cancelled.",
            'cancelled' => $bookings->count(),
        ]);
    }

    private function authorizeBooking(Request $request, SeatBooking $booking): void
    {
        $this->assertCanAccessBranch($request, $booking->seat?->hall?->branch_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBooking(SeatBooking $booking, bool $detailed = false): array
    {
        $branch = $booking->seat?->hall?->branch;
        $schedule = $branch ? LibraryScheduleService::forBranch($branch) : null;

        $payload = [
            'id' => $booking->id,
            'student_id' => $booking->student_id,
            'student_code' => $booking->student?->student_code,
            'student_name' => $booking->student?->name,
            'hall_name' => $booking->seat?->hall?->name,
            'seat_number' => $booking->seat?->seat_number,
            'time_slot' => $booking->time_slot,
            'time_slot_label' => $schedule
                ? $schedule->slotLabel(
                    $booking->time_slot,
                    $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null,
                    $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null,
                )
                : str_replace('_', ' ', $booking->time_slot ?? ''),
            'fee_type' => $booking->fee_type,
            'fee_amount' => $booking->fee_amount,
            'joining_date' => $booking->joining_date?->format('M d, Y'),
            'plan_expiry_date' => $booking->plan_expiry_date?->format('M d, Y'),
            'status' => $booking->status,
            'membership_mode' => $booking->membership_mode,
        ];

        if ($detailed) {
            $student = $booking->student;

            $payload['hall_id'] = $booking->seat?->hall_id;
            $payload['seat_id'] = $booking->seat_id;
            $payload['custom_start_time'] = $booking->custom_start_time
                ? substr((string) $booking->custom_start_time, 0, 5)
                : null;
            $payload['custom_end_time'] = $booking->custom_end_time
                ? substr((string) $booking->custom_end_time, 0, 5)
                : null;
            $payload['membership_mode_label'] = str_replace('_', ' ', $booking->membership_mode ?? '');
            if ($branch && $booking->seat) {
                $payload['today_windows'] = SeatAvailabilityService::forBranch($branch)
                    ->availabilityTimeline($booking->seat);
            }
            $payload['student'] = $student ? [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'name' => $student->name,
                'phone' => $student->phone,
                'email' => $student->email,
                'gender' => $student->gender,
                'gender_label' => $student->gender ? ucfirst($student->gender) : null,
                'father_name' => $student->father_name,
                'address' => $student->address,
                'status' => $student->status,
                'photo_url' => $student->photoUrl(),
                'initials' => $student->initials(),
                'id_proof_type' => $student->id_proof_type,
                'id_proof_url' => $student->idProofUrl(),
            ] : null;
        }

        return $payload;
    }
}
