<?php

namespace App\Addons\Attendance\Http\Controllers\Api;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Services\AttendanceService;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StudentAttendanceApiController extends Controller
{
    public function index(Request $request, AttendanceService $attendance): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $asOf = Carbon::now($tz)->startOfDay();
        $profile = $attendance->studentProfile($student, $asOf);

        $from = $asOf->copy()->subDays(59);
        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $asOf->toDateString())
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->attendance_date?->toDateString());

        $student->loadMissing('branch');
        $marks = [];

        foreach ($records as $date => $record) {
            if (! $date) {
                continue;
            }

            $status = $student->branch && $attendance->isLateCheckIn($student->branch, $record->check_in_at)
                ? 'late'
                : 'present';
            $marks[$date] = $status;
        }

        $allRecords = $records->sortByDesc('attendance_date')->values()->map(function (AttendanceRecord $record) use ($student, $attendance) {
            $status = $student->branch && $attendance->isLateCheckIn($student->branch, $record->check_in_at)
                ? 'late'
                : 'present';

            return [
                'date_label' => $record->attendance_date?->format('M d, Y'),
                'status' => $status,
                'status_label' => ucfirst($status),
                'check_in_at' => $record->check_in_at?->format('h:i A'),
            ];
        })->all();

        return response()->json([
            'summary' => [
                'rate' => $profile['attendance_rate'],
                'present' => $profile['stats']['present'],
                'absent' => $profile['stats']['absent'],
                'late' => $profile['stats']['late'],
            ],
            'recent' => $profile['recent'],
            'records' => $allRecords,
            'marks' => $marks,
        ]);
    }

    public function checkIn(Request $request, AttendanceService $attendance): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $validated = $request->validate([
            'qr_token' => ['nullable', 'string', 'max:120'],
            'qr_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $token = $validated['qr_token'] ?? $this->extractQrToken($validated['qr_url'] ?? null);

        if (! $token) {
            throw ValidationException::withMessages([
                'qr_url' => 'Scan the attendance QR code displayed at your library.',
            ]);
        }

        $settings = $attendance->settingsByToken($token);

        abort_unless($settings && $settings->student_qr_enabled, 404, 'Attendance QR is not available.');

        $settings->load('branch');
        $branch = $settings->branch;
        abort_unless($branch, 404);

        abort_unless((int) $student->branch_id === (int) $branch->id, 403, 'This QR code belongs to a different branch.');

        try {
            $record = $attendance->checkInStudent(
                $student,
                AttendanceRecord::METHOD_STUDENT_QR,
                meta: [
                    'source' => 'flutter_app',
                    'device_name' => $request->input('device_name'),
                ],
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();

            return response()->json([
                'message' => is_string($message) && $message !== ''
                    ? $message
                    : 'Already checked in for today.',
                'already_checked_in' => true,
            ], 422);
        }

        return response()->json([
            'message' => 'Check-in successful.',
            'record' => [
                'check_in_at' => $record->check_in_at?->format('h:i A'),
                'attendance_date' => $record->attendance_date?->toDateString(),
            ],
        ]);
    }

    private function extractQrToken(?string $qrUrl): ?string
    {
        if (! $qrUrl) {
            return null;
        }

        $path = parse_url(trim($qrUrl), PHP_URL_PATH);

        if (! is_string($path) || ! preg_match('#/attendance/check-in/([^/]+)#', $path, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
