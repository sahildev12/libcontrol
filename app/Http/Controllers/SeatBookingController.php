<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSeatBookingRequest;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\PlanExpiryService;
use App\Services\SeatConflictService;
use App\Services\SeatMapService;
use App\Services\LibraryScheduleService;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SeatBookingController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $this->activeBranchId($request);

        $bookings = SeatBooking::query()
            ->with(['student', 'seat.hall.branch'])
            ->whereHas('seat.hall', fn ($query) => $query->where('branch_id', $branchId))
            ->whereNull('cancelled_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SeatBooking $booking) => $this->serializeBooking($booking));

        $students = Student::query()
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'student_code', 'name', 'phone', 'student_type']);

        $halls = Hall::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $schedule = LibraryScheduleService::forBranch($this->activeBranch($request));

        return view('seat-assignments.index', [
            'bookings' => $bookings,
            'students' => $students,
            'halls' => $halls,
            'timeSlotOptions' => $schedule->timeSlotOptions(),
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
        ]);

        abort_unless(
            Hall::query()->where('id', $validated['hall_id'])->where('branch_id', $this->activeBranchId($request))->exists(),
            403,
        );

        $branch = $this->activeBranch($request);
        $conflictService = SeatConflictService::forBranch($branch);

        $joining = Carbon::parse($validated['joining_date']);
        $expiry = Carbon::parse($validated['plan_expiry_date']);

        $availability = SeatAvailabilityService::forBranch($branch);

        $seats = Seat::query()
            ->with('bookings.student')
            ->where('hall_id', $validated['hall_id'])
            ->orderBy('seat_number')
            ->get()
            ->filter(function (Seat $seat) use ($conflictService, $validated, $joining, $expiry) {
                return ! $conflictService->hasConflict(
                    $seat->id,
                    $validated['time_slot'],
                    $joining,
                    $expiry,
                    $validated['custom_start_time'] ?? null,
                    $validated['custom_end_time'] ?? null,
                );
            })
            ->values()
            ->map(fn (Seat $seat) => [
                'id' => $seat->id,
                'seat_number' => $seat->seat_number,
                'today_windows' => $availability->availabilityTimeline($seat),
            ]);

        return response()->json(['seats' => $seats]);
    }

    public function store(
        StoreSeatBookingRequest $request,
        PlanExpiryService $planExpiryService,
        SeatMapService $seatMapService,
    ): JsonResponse {
        $branchId = $this->activeBranchId($request);
        $branch = $this->activeBranch($request);
        $conflictService = SeatConflictService::forBranch($branch);

        $student = Student::query()->where('id', $request->integer('student_id'))->where('branch_id', $branchId)->firstOrFail();
        $seat = Seat::query()->with('hall')->where('id', $request->integer('seat_id'))->firstOrFail();

        abort_unless($seat->hall?->branch_id === $branchId, 403);
        abort_unless($request->integer('hall_id') === $seat->hall_id, 422, 'Seat does not belong to selected hall.');

        if ($student->student_type !== Student::TYPE_REGULAR) {
            $student->update(['student_type' => Student::TYPE_REGULAR]);
        }

        $joining = Carbon::parse($request->input('joining_date'));
        $expiry = $request->filled('plan_expiry_date')
            ? Carbon::parse($request->input('plan_expiry_date'))
            : $planExpiryService->calculate($request->input('fee_type'), $joining);

        if ($conflictService->hasConflict(
            $seat->id,
            $request->input('time_slot'),
            $joining,
            $expiry,
            $request->input('custom_start_time'),
            $request->input('custom_end_time'),
        )) {
            return response()->json(['message' => 'This seat has a conflicting assignment for the selected time slot and dates.'], 422);
        }

        $booking = SeatBooking::create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => $request->input('time_slot'),
            'custom_start_time' => $request->input('custom_start_time'),
            'custom_end_time' => $request->input('custom_end_time'),
            'fee_type' => $request->input('fee_type'),
            'fee_amount' => $request->input('fee_amount'),
            'membership_mode' => $request->input('membership_mode'),
            'joining_date' => $joining,
            'plan_expiry_date' => $expiry,
            'status' => 'occupied',
        ]);

        $booking->load(['student:id,student_code,name', 'seat.hall:id,name']);
        $seatMapService->broadcastForBranch($branchId);

        return response()->json([
            'message' => 'Seat assigned successfully.',
            'booking' => $this->serializeBooking($booking),
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

        $seatMapService->broadcastForBranch($this->activeBranchId($request));

        return response()->json(['message' => 'Seat assignment cancelled.']);
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

        $branchId = $this->activeBranchId($request);

        $bookings = SeatBooking::query()
            ->with('seat.hall')
            ->whereIn('id', $validated['ids'])
            ->whereNull('cancelled_at')
            ->get()
            ->filter(fn (SeatBooking $booking) => $booking->seat?->hall?->branch_id === $branchId);

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }

        $seatMapService->broadcastForBranch($branchId);

        return response()->json([
            'message' => "{$bookings->count()} assignment(s) cancelled.",
            'cancelled' => $bookings->count(),
        ]);
    }

    private function authorizeBooking(Request $request, SeatBooking $booking): void
    {
        abort_unless(
            $booking->seat?->hall?->branch_id === $this->activeBranchId($request),
            403,
        );
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
