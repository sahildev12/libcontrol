<?php

namespace App\Addons\Attendance\Services;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Models\BranchAttendanceSetting;
use App\Models\Branch;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\FeeService;
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

    /**
     * @param  array<string, mixed>  $meta
     */
    public function checkOutStudent(
        Student $student,
        array $meta = [],
        ?Carbon $at = null,
    ): AttendanceRecord {
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $now = $at ?? Carbon::now($tz);
        $date = $now->copy()->startOfDay();

        $record = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'action' => 'You must check in before you can check out.',
            ]);
        }

        if ($record->check_out_at !== null) {
            throw ValidationException::withMessages([
                'action' => 'You have already checked out for today.',
            ]);
        }

        $record->update([
            'check_out_at' => $now,
            'device_meta' => array_merge($record->device_meta ?? [], $meta),
        ]);

        return $record->fresh();
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

    /**
     * @return array<string, mixed>
     */
    public function studentReport(
        Student $student,
        Carbon $from,
        Carbon $to,
        Carbon $calendarMonth,
        ?string $selectedDate = null,
    ): array {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $calendarMonth = $calendarMonth->copy()->startOfMonth();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $student->loadMissing(['branch', 'bookings' => fn ($query) => $query
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->with('seat.hall')
            ->latest('id'),
        ]);

        $booking = $student->bookings->first();
        $records = AttendanceRecord::query()
            ->with(['branch', 'markedBy:id,name'])
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->orderByDesc('attendance_date')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->attendance_date?->toDateString());

        $dayStatuses = $this->buildStudentDayStatuses($student, $from, $to, $records, $booking);
        $summary = $this->summarizeStudentDayStatuses($dayStatuses);
        $calendar = $this->buildStudentCalendar($student, $calendarMonth, $from, $to, $records, $booking);
        $selected = $this->resolveSelectedReportDate($from, $to, $selectedDate, $dayStatuses);
        $selectedDay = $this->serializeStudentDayDetail(
            $student,
            Carbon::parse($selected, config('libcontrol.timezone', 'Asia/Kolkata')),
            $records->get($selected),
            $booking,
        );

        return [
            'student' => $this->serializeStudentReportCard($student, $booking),
            'summary' => $summary,
            'calendar' => $calendar,
            'selected_date' => $selected,
            'selected_day' => $selectedDay,
            'recent_activity' => $this->buildStudentRecentActivity($student, $from, $to, $records, $booking),
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'calendar_month' => $calendarMonth->format('Y-m'),
            'calendar_month_label' => $calendarMonth->format('F Y'),
            'has_data' => $records->isNotEmpty() || $summary['total_days'] > 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function studentReportExportRows(Student $student, Carbon $from, Carbon $to): array
    {
        $student->loadMissing(['branch', 'bookings' => fn ($query) => $query
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->with('seat.hall')
            ->latest('id'),
        ]);

        $booking = $student->bookings->first();
        $card = $this->serializeStudentReportCard($student, $booking);
        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->attendance_date?->toDateString());

        $rows = [];
        $cursor = $from->copy();

        while ($cursor->lessThanOrEqualTo($to)) {
            $iso = $cursor->toDateString();
            $day = $this->serializeStudentDayDetail(
                $student,
                $cursor->copy(),
                $records->get($iso),
                $booking,
            );

            $rows[] = [
                'student_name' => $card['name'],
                'student_code' => $card['student_code'],
                'branch' => $card['branch_name'],
                'date' => $day['date_heading'] ?? $day['date'] ?? $iso,
                'status' => $day['status_label'],
                'check_in' => $day['check_in_at'] ?? '—',
                'check_out' => $day['check_out_at'] ?? '—',
                'study_time' => $day['study_time_label'] ?? '—',
                'method' => $day['method_label'] ?? '—',
                'location' => $day['location_name'] ?? '—',
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @param  Collection<string, AttendanceRecord>  $records
     * @return array<string, string>
     */
    private function buildStudentDayStatuses(
        Student $student,
        Carbon $from,
        Carbon $to,
        Collection $records,
        ?SeatBooking $booking,
    ): array {
        $statuses = [];
        $cursor = $from->copy();

        while ($cursor->lessThanOrEqualTo($to)) {
            $iso = $cursor->toDateString();
            $statuses[$iso] = $this->resolveStudentDayStatus($student, $cursor, $records->get($iso), $booking);
            $cursor->addDay();
        }

        return $statuses;
    }

    /**
     * @param  array<string, string>  $dayStatuses
     * @return array<string, int|float>
     */
    private function summarizeStudentDayStatuses(array $dayStatuses): array
    {
        $present = 0;
        $absent = 0;
        $trialHalfDay = 0;
        $late = 0;

        foreach ($dayStatuses as $status) {
            match ($status) {
                'present' => $present++,
                'absent' => $absent++,
                'trial' => $trialHalfDay++,
                'late' => $late++,
                default => null,
            };
        }

        $totalDays = count($dayStatuses);

        return [
            'present' => $present,
            'absent' => $absent,
            'trial_half_day' => $trialHalfDay,
            'late' => $late,
            'total_days' => $totalDays,
            'present_pct' => $this->percentageOf($present, $totalDays),
            'absent_pct' => $this->percentageOf($absent, $totalDays),
            'trial_half_day_pct' => $this->percentageOf($trialHalfDay, $totalDays),
            'late_pct' => $this->percentageOf($late, $totalDays),
        ];
    }

    /**
     * @param  Collection<string, AttendanceRecord>  $records
     * @return array<string, mixed>
     */
    private function buildStudentCalendar(
        Student $student,
        Carbon $calendarMonth,
        Carbon $from,
        Carbon $to,
        Collection $records,
        ?SeatBooking $booking,
    ): array {
        $start = $calendarMonth->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $iso = $cursor->toDateString();
                $inMonth = $cursor->month === $calendarMonth->month;
                $inRange = $cursor->betweenIncluded($from, $to);
                $status = null;

                if ($inMonth && $inRange) {
                    $status = $this->resolveStudentDayStatus($student, $cursor, $records->get($iso), $booking);
                }

                $week[] = [
                    'date' => $iso,
                    'day' => $cursor->day,
                    'in_month' => $inMonth,
                    'in_range' => $inRange,
                    'status' => $status,
                    'is_today' => $cursor->isToday(),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return [
            'weeks' => $weeks,
            'weekday_labels' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ];
    }

    /**
     * @param  array<string, string>  $dayStatuses
     */
    private function resolveSelectedReportDate(Carbon $from, Carbon $to, ?string $selectedDate, array $dayStatuses): string
    {
        if ($selectedDate && isset($dayStatuses[$selectedDate])) {
            return $selectedDate;
        }

        $today = Carbon::today(config('libcontrol.timezone', 'Asia/Kolkata'))->toDateString();

        if (isset($dayStatuses[$today])) {
            return $today;
        }

        return array_key_last($dayStatuses) ?: $from->toDateString();
    }

    /**
     * @param  Collection<string, AttendanceRecord>  $records
     * @return list<array<string, mixed>>
     */
    private function buildStudentRecentActivity(
        Student $student,
        Carbon $from,
        Carbon $to,
        Collection $records,
        ?SeatBooking $booking,
    ): array {
        $items = [];
        $cursor = $to->copy();

        while ($cursor->greaterThanOrEqualTo($from)) {
            $iso = $cursor->toDateString();

            $detail = $this->serializeStudentDayDetail($student, $cursor->copy(), $records->get($iso), $booking);
            $items[] = [
                'date' => $iso,
                'date_label' => $detail['date_heading'],
                'status' => $detail['status'],
                'status_label' => $detail['status_label'],
                'study_time_label' => $detail['study_time_label'],
            ];
            $cursor->subDay();
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStudentReportCard(Student $student, ?SeatBooking $booking): array
    {
        $feeService = app(FeeService::class);
        $feeType = $booking ? $feeService->normalizeFeeType((string) $booking->fee_type) : 'monthly';
        $seatNumber = $booking?->seat?->seat_number;
        $hallName = $booking?->seat?->hall?->name;

        return [
            'id' => $student->id,
            'name' => $student->name,
            'student_code' => $student->student_code,
            'photo_url' => $student->photoUrl(),
            'initials' => $student->initials(),
            'status' => $student->status,
            'status_label' => ucfirst((string) $student->status),
            'membership_plan' => $student->isTrialStudent()
                ? 'Trial'
                : ($booking ? $feeService->feeTypeLabel($feeType).' Plan' : '—'),
            'valid_till' => $booking?->plan_expiry_date?->format('d M Y'),
            'valid_till_label' => $booking?->plan_expiry_date?->format('d M Y') ?? '—',
            'branch_name' => $student->branch?->name,
            'seat_no' => $seatNumber && $hallName
                ? $hallName.' — #'.$seatNumber
                : ($seatNumber ? '#'.$seatNumber : null),
            'contact' => $student->effectivePhone() ?: $student->phone,
            'student_profile_url' => route('students.show', $student),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStudentDayDetail(
        Student $student,
        Carbon $date,
        ?AttendanceRecord $record,
        ?SeatBooking $booking,
    ): array {
        $status = $this->resolveStudentDayStatus($student, $date, $record, $booking);
        $branch = $record?->branch ?? $student->branch;
        $locationName = $branch?->name;

        if ($status === 'absent') {
            return [
                'date' => $date->toDateString(),
                'date_heading' => $date->format('M d, Y').' ('.$date->format('l').')',
                'status' => 'absent',
                'status_label' => 'Absent',
                'message' => 'No attendance recorded for this date.',
                'check_in_at' => null,
                'check_out_at' => null,
                'study_time_label' => null,
                'method' => null,
                'method_label' => null,
                'location_name' => $locationName,
                'has_location' => false,
                'latitude' => null,
                'longitude' => null,
            ];
        }

        return [
            'date' => $date->toDateString(),
            'date_heading' => $date->format('M d, Y').' ('.$date->format('l').')',
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'message' => null,
            'check_in_at' => $record?->check_in_at?->format('h:i A'),
            'check_out_at' => $record?->check_out_at?->format('h:i A'),
            'study_time_label' => ($record?->check_in_at && $record?->check_out_at)
                ? $record->check_in_at->diffForHumans($record->check_out_at, true)
                : null,
            'study_time_note' => $record?->check_out_at === null
                ? 'Check-out is not recorded, so total study time cannot be calculated.'
                : null,
            'method' => $record?->method,
            'method_label' => $this->methodLabel($record?->method),
            'marked_by_name' => $record?->markedBy?->name,
            'location_name' => $locationName,
            'has_location' => $record?->latitude !== null && $record?->longitude !== null,
            'latitude' => $record?->latitude !== null ? (float) $record->latitude : null,
            'longitude' => $record?->longitude !== null ? (float) $record->longitude : null,
            'maps_url' => ($record?->latitude !== null && $record?->longitude !== null)
                ? 'https://www.google.com/maps?q='.$record->latitude.','.$record->longitude
                : null,
        ];
    }

    private function resolveStudentDayStatus(
        Student $student,
        Carbon $date,
        ?AttendanceRecord $record,
        ?SeatBooking $booking,
    ): string {
        if (! $record) {
            return 'absent';
        }

        if ($this->studentWasOnTrial($student, $date, $booking)) {
            return 'trial';
        }

        if ($student->branch && $this->isLateCheckIn($student->branch, $record->check_in_at)) {
            return 'late';
        }

        return 'present';
    }

    private function studentWasOnTrial(Student $student, Carbon $date, ?SeatBooking $booking): bool
    {
        if ($student->student_type === Student::TYPE_TRIAL) {
            return true;
        }

        if (! $booking) {
            return false;
        }

        if ($booking->trial_start && $booking->trial_end) {
            return $date->betweenIncluded($booking->trial_start, $booking->trial_end);
        }

        return false;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'trial' => 'Trial / Half Day',
            default => ucfirst($status),
        };
    }

    private function percentageOf(int $count, int $total): float
    {
        if ($total <= 0 || $count <= 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 0);
    }

    public function methodLabel(?string $method): string
    {
        return match ($method) {
            AttendanceRecord::METHOD_STUDENT_QR => 'Student QR',
            AttendanceRecord::METHOD_STAFF_GPS => 'Staff GPS',
            AttendanceRecord::METHOD_MANUAL => 'Staff Marked',
            AttendanceRecord::METHOD_BIOMETRIC => 'Biometric',
            default => '—',
        };
    }
}
