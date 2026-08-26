<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\SeatAvailabilityService;
use App\Services\SeatMapService;
use App\Services\SeatStatusService;
use App\Services\LibraryScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SeatController extends Controller
{
    public function index(Request $request, SeatMapService $seatMapService): View
    {
        $branchId = $this->optionalActiveBranchId($request);
        $payload = $seatMapService->payloadForBranch($branchId);
        $statusService = app(SeatStatusService::class);
        $seats = array_values(array_filter(
            $payload['seats'],
            fn (array $seat) => $statusService->visibleOnRegularMap($seat),
        ));

        $students = $this->constrainByActiveBranch(Student::query(), $request)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'student_code', 'name', 'phone', 'student_type', 'branch_id']);

        $branches = $request->user()?->isPlatformAdmin()
            ? \App\Models\Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('seats.index', [
            'halls' => $payload['halls'],
            'seats' => $seats,
            'students' => $students,
            'assignedStudents' => $this->assignedStudentsForTransfer($request),
            'timeSlotOptions' => $payload['time_slot_options'],
            'branches' => $branches,
            'defaultBranchId' => $branchId ?: $branches->first()?->id,
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
            fn (array $seat) => $statusService->visibleOnRegularMap($seat),
        ));
        $payload['assigned_students'] = $this->assignedStudentsForTransfer($request);

        return response()->json($payload);
    }

    public function schedule(Request $request, Seat $seat): JsonResponse
    {
        $seat->load(['hall.branch', 'bookings.student']);
        $this->assertCanAccessBranch($request, $seat->hall?->branch_id);

        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();
        $branch = $seat->hall?->branch;
        $schedule = $branch ? LibraryScheduleService::forBranch($branch) : null;
        $availability = $branch ? SeatAvailabilityService::forBranch($branch) : null;
        $windows = $availability ? $availability->availabilityTimeline($seat, $date) : [];

        $bookings = $seat->bookings
            ->filter(function (SeatBooking $booking) use ($date) {
                if ($booking->cancelled_at !== null || $booking->status === 'cancelled') {
                    return false;
                }

                if ($booking->joining_date && $booking->joining_date->gt($date)) {
                    return false;
                }

                $expiry = $booking->trial_end ?? $booking->plan_expiry_date;
                if ($expiry && $expiry->lt($date)) {
                    return false;
                }

                return true;
            })
            ->sortBy(function (SeatBooking $booking) {
                return $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : '00:00';
            })
            ->values()
            ->map(function (SeatBooking $booking) use ($schedule) {
                $isTrial = $booking->status === 'on_trial'
                    || $booking->trial_end
                    || $booking->student?->student_type === Student::TYPE_TRIAL;
                $start = $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null;
                $end = $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null;

                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->id,
                    'student_id' => $booking->student_id,
                    'student_name' => $booking->student?->name,
                    'student_code' => $booking->student?->student_code,
                    'student_initial' => $booking->student
                        ? strtoupper(substr((string) $booking->student->name, 0, 1))
                        : null,
                    'student_type' => $isTrial ? 'trial' : 'regular',
                    'is_trial' => $isTrial,
                    'time_slot' => $booking->time_slot,
                    'time_slot_label' => $schedule
                        ? $schedule->slotLabel($booking->time_slot, $start, $end)
                        : str_replace('_', ' ', (string) $booking->time_slot),
                    'from' => $start,
                    'to' => $end,
                    'joining_date' => $booking->joining_date?->format('M d, Y'),
                    'joining_date_iso' => $booking->joining_date?->toDateString(),
                    'plan_expiry_date' => ($booking->trial_end ?? $booking->plan_expiry_date)?->format('M d, Y'),
                    'plan_expiry_date_iso' => ($booking->trial_end ?? $booking->plan_expiry_date)?->toDateString(),
                    'status' => $booking->status,
                ];
            })
            ->all();

        return response()->json([
            'seat' => [
                'id' => $seat->id,
                'seat_number' => $seat->seat_number,
                'hall_name' => $seat->hall?->name,
            ],
            'date' => $date->toDateString(),
            'date_label' => $date->format('d M Y'),
            'bookings' => $bookings,
            'today_windows' => $windows,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function assignedStudentsForTransfer(Request $request): array
    {
        /** @var Collection<int, SeatBooking> $assignedBookings */
        $assignedBookings = $this->constrainByActiveSeatHall(SeatBooking::query(), $request)
            ->with(['student:id,student_code,name,phone', 'seat.hall.branch'])
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->whereHas('student', fn ($query) => $query->where('status', 'active'))
            ->orderByDesc('id')
            ->get();

        return $assignedBookings
            ->unique('student_id')
            ->values()
            ->map(function (SeatBooking $booking) {
                $branch = $booking->seat?->hall?->branch;
                $schedule = $branch ? LibraryScheduleService::forBranch($branch) : null;
                $start = $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null;
                $end = $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null;

                return [
                    'id' => $booking->student_id,
                    'student_code' => $booking->student?->student_code,
                    'name' => $booking->student?->name,
                    'phone' => $booking->student?->phone,
                    'booking_id' => $booking->id,
                    'hall_id' => $booking->seat?->hall_id,
                    'hall_name' => $booking->seat?->hall?->name,
                    'seat_id' => $booking->seat_id,
                    'seat_number' => $booking->seat?->seat_number,
                    'time_slot' => $booking->time_slot,
                    'time_slot_label' => $schedule
                        ? $schedule->slotLabel($booking->time_slot, $start, $end)
                        : str_replace('_', ' ', (string) $booking->time_slot),
                    'custom_start_time' => $start,
                    'custom_end_time' => $end,
                    'joining_date' => $booking->joining_date?->toDateString(),
                    'joining_date_label' => $booking->joining_date?->format('M d, Y'),
                    'plan_expiry_date' => $booking->plan_expiry_date?->toDateString(),
                    'plan_expiry_date_label' => $booking->plan_expiry_date?->format('M d, Y'),
                    'status' => $booking->status,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
