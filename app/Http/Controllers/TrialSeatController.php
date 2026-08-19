<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrialSeatBookingRequest;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\SeatAvailabilityService;
use App\Services\SeatConflictService;
use App\Services\SeatMapService;
use App\Services\SeatStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TrialSeatController extends Controller
{
    public function index(Request $request, SeatMapService $seatMapService): View
    {
        $branchId = $this->activeBranchId($request);
        $branch = $this->activeBranch($request);
        $payload = $seatMapService->payloadForBranch($branchId);
        $seats = array_values(array_filter(
            $payload['seats'],
            fn (array $seat) => empty($seat['has_regular_assignment']),
        ));

        $students = Student::query()
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->where('student_type', Student::TYPE_TRIAL)
            ->orderBy('name')
            ->get(['id', 'student_code', 'name', 'phone', 'student_type']);

        return view('trial-seats.index', [
            'halls' => $payload['halls'],
            'seats' => $seats,
            'students' => $students,
            'timeSlotOptions' => $payload['time_slot_options'],
            'branchName' => $branch->name,
        ]);
    }

    public function data(Request $request, SeatMapService $seatMapService): JsonResponse
    {
        $payload = $seatMapService->payloadForBranch($this->activeBranchId($request));
        $payload['seats'] = array_values(array_filter(
            $payload['seats'],
            fn (array $seat) => empty($seat['has_regular_assignment']),
        ));

        return response()->json($payload);
    }

    public function availableSeats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'time_slot' => ['required', 'in:full_day,custom_hours'],
            'trial_start' => ['required', 'date'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:14'],
            'custom_start_time' => ['nullable', 'date_format:H:i'],
            'custom_end_time' => ['nullable', 'date_format:H:i'],
        ]);

        abort_unless(
            Hall::query()->where('id', $validated['hall_id'])->where('branch_id', $this->activeBranchId($request))->exists(),
            403,
        );

        $branch = $this->activeBranch($request);
        $conflictService = SeatConflictService::forBranch($branch);
        $availability = SeatAvailabilityService::forBranch($branch);
        $start = Carbon::parse($validated['trial_start']);
        $end = $start->copy()->addDays($validated['trial_days'] - 1);

        $statusService = app(SeatStatusService::class);

        $seats = Seat::query()
            ->with('bookings.student')
            ->where('hall_id', $validated['hall_id'])
            ->orderBy('seat_number')
            ->get()
            ->filter(function (Seat $seat) use ($conflictService, $statusService, $validated, $start, $end) {
                if ($statusService->hasActiveRegularAssignment($seat)) {
                    return false;
                }

                return ! $conflictService->hasConflictForDateRange(
                    $seat->id,
                    $validated['time_slot'],
                    $start,
                    $end,
                    $validated['custom_start_time'] ?? null,
                    $validated['custom_end_time'] ?? null,
                );
            })
            ->values()
            ->map(fn (Seat $seat) => [
                'id' => $seat->id,
                'seat_number' => $seat->seat_number,
                'free_hours_today' => $availability->freeWindowsLabel($seat),
                'today_windows' => $availability->availabilityTimeline($seat),
            ]);

        return response()->json(['seats' => $seats]);
    }

    public function store(
        StoreTrialSeatBookingRequest $request,
        SeatMapService $seatMapService,
    ): JsonResponse {
        $branchId = $this->activeBranchId($request);
        $branch = $this->activeBranch($request);
        $conflictService = SeatConflictService::forBranch($branch);

        $student = Student::query()
            ->where('id', $request->integer('student_id'))
            ->where('branch_id', $branchId)
            ->firstOrFail();

        if ($student->student_type !== Student::TYPE_TRIAL) {
            $student->update(['student_type' => Student::TYPE_TRIAL]);
        }

        $seat = Seat::query()->with('hall')->where('id', $request->integer('seat_id'))->firstOrFail();

        abort_unless($seat->hall?->branch_id === $branchId, 403);
        abort_unless($request->integer('hall_id') === $seat->hall_id, 422, 'Seat does not belong to selected hall.');

        $seat->load('bookings.student');

        $trialStart = Carbon::parse($request->input('trial_start'));
        $trialDays = $request->integer('trial_days');
        $trialEnd = $trialStart->copy()->addDays($trialDays - 1);

        if ($conflictService->hasConflictForDateRange(
            $seat->id,
            $request->input('time_slot'),
            $trialStart,
            $trialEnd,
            $request->input('custom_start_time'),
            $request->input('custom_end_time'),
        )) {
            return response()->json(['message' => 'This seat is not available for the selected trial period and time slot.'], 422);
        }

        $booking = SeatBooking::create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => $request->input('time_slot'),
            'custom_start_time' => $request->input('custom_start_time'),
            'custom_end_time' => $request->input('custom_end_time'),
            'fee_type' => 'custom',
            'fee_amount' => $request->input('fee_amount', 0),
            'membership_mode' => 'assigned_seat',
            'joining_date' => $trialStart,
            'plan_expiry_date' => $trialEnd,
            'trial_start' => $trialStart,
            'trial_end' => $trialEnd,
            'status' => 'on_trial',
        ]);

        $seatMapService->broadcastForBranch($branchId);

        return response()->json([
            'message' => 'Trial seat assigned successfully.',
            'booking_id' => $booking->id,
        ], 201);
    }
}
