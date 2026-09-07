<?php

namespace App\Addons\Attendance\Services;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Models\BranchAttendanceSetting;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use App\Services\LibraryScheduleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function settingsForBranch(int $branchId): BranchAttendanceSetting
    {
        return BranchAttendanceSetting::forBranch($branchId);
    }

    public function settingsByToken(string $token): ?BranchAttendanceSetting
    {
        return BranchAttendanceSetting::query()
            ->with('branch')
            ->where('qr_token', $token)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(?int $branchId, Carbon $date): array
    {
        $studentsQuery = Student::query()->where('status', 'active');
        if ($branchId) {
            $studentsQuery->where('branch_id', $branchId);
        }

        $totalStudents = (int) $studentsQuery->count();

        $recordsQuery = AttendanceRecord::query()->whereDate('attendance_date', $date->toDateString());
        if ($branchId) {
            $recordsQuery->where('branch_id', $branchId);
        }

        $present = (int) (clone $recordsQuery)->count();
        $qrCount = (int) (clone $recordsQuery)->where('method', AttendanceRecord::METHOD_STUDENT_QR)->count();
        $staffCount = (int) (clone $recordsQuery)->where('method', AttendanceRecord::METHOD_STAFF_GPS)->count();
        $manualCount = (int) (clone $recordsQuery)->where('method', AttendanceRecord::METHOD_MANUAL)->count();
        $biometricCount = (int) (clone $recordsQuery)->where('method', AttendanceRecord::METHOD_BIOMETRIC)->count();

        $late = 0;
        if ($branchId) {
            $branch = Branch::query()->find($branchId);
            if ($branch) {
                $late = (int) (clone $recordsQuery)->get()->filter(
                    fn (AttendanceRecord $record) => $this->isLateCheckIn($branch, $record->check_in_at)
                )->count();
            }
        }

        $onTimePresent = max(0, $present - $late);
        $absent = max(0, $totalStudents - $present);

        return [
            'total_students' => $totalStudents,
            'present' => $onTimePresent,
            'late' => $late,
            'absent' => $absent,
            'not_marked' => 0,
            'qr_check_ins' => $qrCount,
            'staff_check_ins' => $staffCount,
            'manual_check_ins' => $manualCount,
            'biometric_check_ins' => $biometricCount,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function registerRows(?int $branchId, Carbon $date): Collection
    {
        $studentsQuery = Student::query()
            ->where('status', 'active')
            ->with(['branch', 'bookings' => function ($query) {
                $query->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->with('seat.hall')
                    ->latest('id');
            }]);

        if ($branchId) {
            $studentsQuery->where('branch_id', $branchId);
        }

        $students = $studentsQuery->orderBy('name')->get();

        $records = AttendanceRecord::query()
            ->whereDate('attendance_date', $date->toDateString())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->keyBy('student_id');

        return $students->map(function (Student $student) use ($records, $date) {
            $record = $records->get($student->id);
            $booking = $student->bookings->first();
            $branch = $student->branch;
            $status = 'absent';

            if ($record) {
                $status = $branch && $this->isLateCheckIn($branch, $record->check_in_at) ? 'late' : 'present';
            }

            return [
                'student_id' => $student->id,
                'student_code' => $student->student_code,
                'student_name' => $student->name,
                'initials' => $student->initials(),
                'photo_url' => $student->photoUrl(),
                'email' => $student->email,
                'phone' => $student->phone,
                'student_type' => $student->student_type,
                'student_type_label' => $student->typeLabel(),
                'joining_date_label' => $booking?->joining_date?->format('M d, Y'),
                'branch_name' => $student->branch?->name,
                'hall_id' => $booking?->seat?->hall_id,
                'hall_name' => $booking?->seat?->hall?->name,
                'seat_number' => $booking?->seat?->seat_number,
                'seat_label' => $booking?->seat?->hall?->name
                    ? $booking->seat->hall->name.' — #'.$booking->seat->seat_number
                    : null,
                'present' => $record !== null,
                'status' => $status,
                'status_label' => ucfirst($status),
                'check_in_at' => $record?->check_in_at?->format('h:i A'),
                'method' => $record?->method,
                'method_label' => $this->methodLabel($record?->method),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function checkInStudent(
        Student $student,
        string $method,
        ?User $markedBy = null,
        ?float $latitude = null,
        ?float $longitude = null,
        array $meta = [],
        ?Carbon $at = null,
    ): AttendanceRecord {
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $now = $at ?? Carbon::now($tz);
        $date = $now->copy()->startOfDay();

        $existing = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'student_id' => 'Attendance already recorded for today.',
            ]);
        }

        return AttendanceRecord::query()->create([
            'branch_id' => $student->branch_id,
            'student_id' => $student->id,
            'attendance_date' => $date->toDateString(),
            'check_in_at' => $now,
            'method' => $method,
            'marked_by_user_id' => $markedBy?->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'device_meta' => $meta ?: null,
        ]);
    }

    public function verifyStudentCredentials(Branch $branch, string $studentCode, string $phone): ?Student
    {
        $normalizedPhone = trim($phone);

        return Student::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'active')
            ->where('student_code', trim($studentCode))
            ->with('familyGroup')
            ->get()
            ->first(function (Student $student) use ($normalizedPhone) {
                return trim((string) $student->effectivePhone()) === $normalizedPhone;
            });
    }

    public function withinGeofence(BranchAttendanceSetting $settings, float $latitude, float $longitude): bool
    {
        if ($settings->geofence_latitude === null || $settings->geofence_longitude === null) {
            return true;
        }

        $distance = $this->distanceMeters(
            (float) $settings->geofence_latitude,
            (float) $settings->geofence_longitude,
            $latitude,
            $longitude,
        );

        return $distance <= (int) $settings->geofence_radius_meters;
    }

    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * @return array<string, mixed>
     */
    public function staffContext(Branch $branch, Carbon $date): array
    {
        $settings = $this->settingsForBranch($branch->id);
        $rows = $this->registerRows($branch->id, $date);

        return [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
            ],
            'settings' => [
                'staff_gps_enabled' => $settings->staff_gps_enabled,
                'geofence_latitude' => $settings->geofence_latitude !== null ? (float) $settings->geofence_latitude : null,
                'geofence_longitude' => $settings->geofence_longitude !== null ? (float) $settings->geofence_longitude : null,
                'geofence_radius_meters' => (int) $settings->geofence_radius_meters,
            ],
            'summary' => $this->summary($branch->id, $date),
            'students' => $rows->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dailyStats(?int $branchId, Carbon $from, Carbon $to): array
    {
        $stats = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $daySummary = $this->summary($branchId, $cursor);
            $stats[] = [
                'date' => $cursor->toDateString(),
                'date_label' => $cursor->format('M j, Y'),
                ...$daySummary,
            ];
            $cursor->addDay();
        }

        return array_reverse($stats);
    }

    /**
     * @return array<string, mixed>
     */
    public function studentProfile(Student $student, Carbon $asOf): array
    {
        $from = $asOf->copy()->subDays(29)->startOfDay();
        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $asOf->toDateString())
            ->orderByDesc('attendance_date')
            ->get();

        $present = $records->count();
        $late = $student->branch
            ? $records->filter(fn (AttendanceRecord $record) => $this->isLateCheckIn($student->branch, $record->check_in_at))->count()
            : 0;
        $windowDays = 30;
        $rate = $windowDays > 0 ? (int) round(($present / $windowDays) * 100) : 0;

        $booking = $student->bookings()
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->with('seat.hall')
            ->latest('id')
            ->first();

        return [
            'student_id' => $student->id,
            'student_code' => $student->student_code,
            'student_name' => $student->name,
            'initials' => $student->initials(),
            'photo_url' => $student->photoUrl(),
            'email' => $student->email,
            'phone' => $student->phone,
            'student_type_label' => $student->typeLabel(),
            'joining_date_label' => $booking?->joining_date?->format('M d, Y'),
            'seat_label' => $booking?->seat?->hall?->name
                ? $booking->seat->hall->name.' — #'.$booking->seat->seat_number
                : null,
            'attendance_rate' => min(100, $rate),
            'stats' => [
                'present' => max(0, $present - $late),
                'absent' => max(0, $windowDays - $present),
                'late' => $late,
            ],
            'recent' => $records->take(6)->map(function (AttendanceRecord $record) use ($student) {
                $status = $student->branch && $this->isLateCheckIn($student->branch, $record->check_in_at)
                    ? 'late'
                    : 'present';

                return [
                    'date_label' => $record->attendance_date?->format('M d, Y'),
                    'status' => $status,
                    'status_label' => ucfirst($status),
                    'check_in_at' => $record->check_in_at?->format('h:i A'),
                ];
            })->values()->all(),
        ];
    }

    public function isLateCheckIn(Branch $branch, ?Carbon $checkInAt): bool
    {
        if (! $checkInAt) {
            return false;
        }

        $schedule = LibraryScheduleService::forBranch($branch);
        if ($schedule->is24Hours()) {
            return false;
        }

        $openMinutes = $schedule->openMinutes() + 15;
        $checkInMinutes = ($checkInAt->hour * 60) + $checkInAt->minute;

        return $checkInMinutes > $openMinutes;
    }

    private function methodLabel(?string $method): string
    {
        return match ($method) {
            AttendanceRecord::METHOD_STUDENT_QR => 'Student QR',
            AttendanceRecord::METHOD_STAFF_GPS => 'Staff GPS',
            AttendanceRecord::METHOD_MANUAL => 'Manual',
            AttendanceRecord::METHOD_BIOMETRIC => 'Biometric',
            default => '—',
        };
    }
}
