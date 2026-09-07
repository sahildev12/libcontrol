<?php

namespace App\Addons\Attendance\Http\Controllers;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Services\AttendanceService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use App\Services\LibraryScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceService $attendance): View
    {
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfDay();

        $branchId = $this->optionalActiveBranchId($request);
        $branch = $this->optionalActiveBranch($request);
        $libraryHours = '—';

        if ($branch) {
            $schedule = LibraryScheduleService::forBranch($branch);
            $libraryHours = $schedule->is24Hours()
                ? 'Open 24 hours'
                : $schedule->formatMinutes($schedule->openMinutes()).' – '.$schedule->formatMinutes($schedule->closeMinutes());
        }

        $rows = $attendance->registerRows($branchId, $date);
        $halls = $rows
            ->filter(fn (array $row) => ! empty($row['hall_name']))
            ->unique('hall_id')
            ->map(fn (array $row) => ['id' => $row['hall_id'], 'name' => $row['hall_name']])
            ->values();

        return view('attendance.index', [
            'date' => $date->toDateString(),
            'dateLabel' => $date->format('l, M j, Y'),
            'rows' => $rows,
            'summary' => $attendance->summary($branchId, $date),
            'halls' => $halls,
            'libraryHours' => $libraryHours,
            'lastSyncedAt' => Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->format('h:i A'),
            'scopeLabel' => $this->viewingAllBranches($request)
                ? 'all branches'
                : ($branch?->name ?? ''),
        ]);
    }

    public function markPresent(Request $request, AttendanceService $attendance): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'date' => ['nullable', 'date'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $this->assertCanAccessBranch($request, $student->branch_id);

        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $at = $request->filled('date')
            ? Carbon::parse($validated['date'], $tz)->setTimeFrom(Carbon::now($tz))
            : Carbon::now($tz);

        $record = $attendance->checkInStudent(
            $student,
            AttendanceRecord::METHOD_MANUAL,
            $request->user(),
            at: $at,
        );

        $this->logActivity(
            $request,
            'attendance.marked_present',
            "Marked {$student->name} present manually.",
            $record,
            $student->branch_id,
        );

        $date = $at->copy()->startOfDay();
        $branchId = $student->branch_id;

        return response()->json([
            'message' => 'Student marked present.',
            'row' => $attendance->registerRows($branchId, $date)->firstWhere('student_id', $student->id),
            'summary' => $attendance->summary($branchId, $date),
            'profile' => $attendance->studentProfile($student->fresh(['branch', 'bookings.seat.hall']), $date),
        ]);
    }

    public function studentProfile(Request $request, Student $student, AttendanceService $attendance): JsonResponse
    {
        $this->assertCanAccessBranch($request, $student->branch_id);

        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfDay();

        $student->load(['branch', 'bookings' => fn ($query) => $query
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->with('seat.hall')
            ->latest('id'),
        ]);

        return response()->json($attendance->studentProfile($student, $date));
    }

    public function settings(Request $request, AttendanceService $attendance): View
    {
        $branch = $this->resolveWritableBranch($request, $request->integer('branch_id') ?: null);
        $settings = $attendance->settingsForBranch($branch->id);

        $branches = $request->user()?->isPlatformAdmin()
            ? Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('attendance.settings', [
            'branch' => $branch,
            'settings' => $settings,
            'checkInUrl' => $settings->checkInUrl(),
            'branches' => $branches,
            'viewingAll' => $this->viewingAllBranches($request),
        ]);
    }

    public function updateSettings(Request $request, AttendanceService $attendance): JsonResponse
    {
        $branch = $this->resolveWritableBranch($request, $request->integer('branch_id') ?: null);

        $validated = $request->validate([
            'student_qr_enabled' => ['sometimes', 'boolean'],
            'staff_gps_enabled' => ['sometimes', 'boolean'],
            'geofence_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'geofence_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:25', 'max:5000'],
        ]);

        $settings = $attendance->settingsForBranch($branch->id);
        $settings->update($validated);

        $this->logActivity(
            $request,
            'attendance.settings.updated',
            'Updated attendance settings.',
            $settings,
            $branch->id,
        );

        return response()->json([
            'message' => 'Attendance settings saved.',
            'settings' => $settings->fresh(),
            'check_in_url' => $settings->checkInUrl(),
        ]);
    }

    public function rotateQr(Request $request, AttendanceService $attendance): JsonResponse
    {
        $branch = $this->resolveWritableBranch($request, $request->integer('branch_id') ?: null);
        $settings = $attendance->settingsForBranch($branch->id)->rotateQrToken();

        $this->logActivity(
            $request,
            'attendance.qr.rotated',
            'Rotated branch attendance QR token.',
            $settings,
            $branch->id,
        );

        return response()->json([
            'message' => 'QR code link regenerated.',
            'check_in_url' => $settings->checkInUrl(),
        ]);
    }

    public function reports(Request $request, AttendanceService $attendance): View
    {
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $from = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfMonth();
        $to = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'), $tz)->endOfDay()
            : Carbon::now($tz)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $branchId = $this->optionalActiveBranchId($request);

        return view('attendance.reports', [
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'rangeLabel' => $from->format('M j').' – '.$to->format('M j, Y'),
            'dailyStats' => $attendance->dailyStats($branchId, $from, $to),
            'scopeLabel' => $this->viewingAllBranches($request)
                ? 'all branches'
                : ($this->optionalActiveBranch($request)?->name ?? ''),
        ]);
    }
}
