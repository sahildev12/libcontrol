<?php

namespace App\Addons\Attendance\Http\Controllers\Api;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Services\AttendanceService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceApiController extends Controller
{
    public function context(Request $request, AttendanceService $attendance): JsonResponse
    {
        $branch = $this->resolveApiBranch($request);
        $settings = $attendance->settingsForBranch($branch->id);

        abort_unless($settings->staff_gps_enabled, 403, 'Staff GPS attendance is disabled for this branch.');

        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'), $tz)->startOfDay()
            : Carbon::now($tz)->startOfDay();

        return response()->json($attendance->staffContext($branch, $date));
    }

    public function checkIn(Request $request, AttendanceService $attendance): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $branch = $this->resolveApiBranch($request, $validated['branch_id'] ?? null);
        $settings = $attendance->settingsForBranch($branch->id);

        abort_unless($settings->staff_gps_enabled, 403, 'Staff GPS attendance is disabled for this branch.');

        if (! $attendance->withinGeofence($settings, (float) $validated['latitude'], (float) $validated['longitude'])) {
            throw ValidationException::withMessages([
                'location' => 'You are outside the library geofence.',
            ]);
        }

        $student = Student::query()->findOrFail($validated['student_id']);
        abort_unless((int) $student->branch_id === (int) $branch->id, 403);

        $record = $attendance->checkInStudent(
            $student,
            AttendanceRecord::METHOD_STAFF_GPS,
            $request->user(),
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            [
                'device_name' => $request->input('device_name'),
                'source' => 'flutter',
            ],
        );

        return response()->json([
            'message' => 'Attendance recorded.',
            'record' => [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'check_in_at' => $record->check_in_at->toIso8601String(),
                'method' => $record->method,
            ],
        ]);
    }

    public function bulkCheckIn(Request $request, AttendanceService $attendance): JsonResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $branch = $this->resolveApiBranch($request, $validated['branch_id'] ?? null);
        $settings = $attendance->settingsForBranch($branch->id);

        abort_unless($settings->staff_gps_enabled, 403, 'Staff GPS attendance is disabled for this branch.');

        if (! $attendance->withinGeofence($settings, (float) $validated['latitude'], (float) $validated['longitude'])) {
            throw ValidationException::withMessages([
                'location' => 'You are outside the library geofence.',
            ]);
        }

        $marked = [];
        $skipped = [];

        foreach ($validated['student_ids'] as $studentId) {
            $student = Student::query()->find($studentId);

            if (! $student || (int) $student->branch_id !== (int) $branch->id) {
                $skipped[] = ['student_id' => $studentId, 'reason' => 'invalid_branch'];

                continue;
            }

            try {
                $record = $attendance->checkInStudent(
                    $student,
                    AttendanceRecord::METHOD_STAFF_GPS,
                    $request->user(),
                    (float) $validated['latitude'],
                    (float) $validated['longitude'],
                    ['source' => 'flutter_bulk'],
                );
                $marked[] = $record->student_id;
            } catch (ValidationException) {
                $skipped[] = ['student_id' => $studentId, 'reason' => 'already_checked_in'];
            }
        }

        return response()->json([
            'message' => count($marked).' student(s) marked present.',
            'marked' => $marked,
            'skipped' => $skipped,
        ]);
    }

    public function history(Request $request, AttendanceService $attendance): JsonResponse
    {
        $branch = $this->resolveApiBranch($request);
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $date = isset($validated['date'])
            ? Carbon::parse($validated['date'], $tz)->startOfDay()
            : Carbon::now($tz)->startOfDay();

        $rows = $attendance->registerRows($branch->id, $date);

        return response()->json([
            'date' => $date->toDateString(),
            'summary' => $attendance->summary($branch->id, $date),
            'students' => $rows->values()->all(),
        ]);
    }

    private function resolveApiBranch(Request $request, ?int $requestedBranchId = null): Branch
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->branch_id) {
            return Branch::query()->findOrFail((int) $user->branch_id);
        }

        abort_unless($user->isPlatformAdmin(), 403);
        abort_unless($requestedBranchId, 422, 'branch_id is required for platform admins.');

        return Branch::query()->findOrFail($requestedBranchId);
    }
}
