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
        $branchId = $this->optionalActiveBranchId($request);
        $payload = $seatMapService->payloadForBranch($branchId);
        $statusService = app(SeatStatusService::class);
        $seats = array_values(array_filter(
            $payload['seats'],
            fn (array $seat) => $statusService->visibleOnTrialMap($seat),
        ));

        $students = $this->availableTrialStudents($request);

        return view('trial-seats.index', [
            'halls' => $payload['halls'],
            'seats' => $seats,
            'students' => $students,
            'timeSlotOptions' => $payload['time_slot_options'],
            'branches' => \App\Models\Branch::query()->when(
                ! $request->user()?->isPlatformAdmin(),
                fn ($query) => $query->where('id', $branchId)
            )->orderBy('name')->get(['id', 'name']),
            'defaultBranchId' => $branchId,
            'viewingAll' => $this->viewingAllBranches($request),
            'branchName' => $this->viewingAllBranches($request)
                ? 'All branches'
                : ($this->optionalActiveBranch($request)?->name ?? ''),
        ]);
    }

    public function data(Request $request, SeatMapService $seatMapService): JsonResponse
    {
        $payload = $seatMapService->payloadForBranch($this->optionalActiveBranchId($request));
        $statusService = app(SeatStatusService::class);
        $payload['seats'] = array_values(array_filter(
            $payload['seats'],
            fn (array $seat) => $statusService->visibleOnTrialMap($seat),
        ));
        $payload['students'] = $this->availableTrialStudents($request);

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

        $hall = Hall::query()->with('branch')->where('id', $validated['hall_id'])->firstOrFail();
        $this->assertCanAccessBranch($request, $hall->branch_id);

        $branch = $hall->branch;
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
        $seat = Seat::query()->with('hall.branch')->where('id', $request->integer('seat_id'))->firstOrFail();
        $this->assertCanAccessBranch($request, $seat->hall?->branch_id);
        abort_unless($request->integer('hall_id') === $seat->hall_id, 422, 'Seat does not belong to selected hall.');

        $student = Student::query()
            ->where('id', $request->integer('student_id'))
            ->where('branch_id', $seat->hall->branch_id)
            ->firstOrFail();

        if ($student->student_type !== Student::TYPE_TRIAL) {
            $student->update(['student_type' => Student::TYPE_TRIAL]);
        }

        $branch = $seat->hall->branch;
        $conflictService = SeatConflictService::forBranch($branch);
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
            'fee_amount' => round((float) ($request->input('fee_amount') ?? 0), 2),
            'membership_mode' => 'assigned_seat',
            'joining_date' => $trialStart,
            'plan_expiry_date' => $trialEnd,
            'trial_start' => $trialStart,
            'trial_end' => $trialEnd,
            'status' => 'on_trial',
        ]);

        $seatMapService->broadcastForBranch($seat->hall->branch_id);
        $this->logActivity(
            $request,
            'trial.assigned',
            "Assigned trial seat {$seat->seat_number} to {$student->name}.",
            $booking,
            $seat->hall->branch_id,
        );

        return response()->json([
            'message' => 'Trial seat assigned successfully.',
            'booking_id' => $booking->id,
        ], 201);
    }

    /**
     * Trial students with no active seat assignment.
     *
     * @return \Illuminate\Support\Collection<int, Student>
     */
    private function availableTrialStudents(Request $request)
    {
        $today = Carbon::today()->toDateString();

        return $this->constrainByActiveBranch(Student::query(), $request)
            ->where('status', 'active')
            ->where('student_type', Student::TYPE_TRIAL)
            ->whereDoesntHave('bookings', function ($query) use ($today) {
                $query->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($active) use ($today) {
                        $active->whereDate('plan_expiry_date', '>=', $today)
                            ->orWhereDate('trial_end', '>=', $today);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'student_code', 'name', 'phone', 'student_type', 'branch_id']);
    }
}
