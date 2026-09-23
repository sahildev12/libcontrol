<?php

namespace App\Services;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Models\FeePayment;
use App\Models\SeatBooking;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentAuthService
{
    private const PIN_SETUP_TTL_MINUTES = 15;

    public function findActiveStudentByCode(string $studentCode): Student
    {
        $student = Student::query()
            ->where('student_code', trim($studentCode))
            ->where('status', 'active')
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'student_code' => ['The student code is incorrect or inactive.'],
            ]);
        }

        return $student->load(['branch:id,name,display_name', 'familyGroup']);
    }

    public function issuePinSetupToken(Student $student): string
    {
        $token = Str::random(64);

        Cache::put(
            $this->pinSetupCacheKey($token),
            $student->id,
            now()->addMinutes(self::PIN_SETUP_TTL_MINUTES),
        );

        return $token;
    }

    public function completePinSetup(string $token, string $pin): Student
    {
        $studentId = Cache::pull($this->pinSetupCacheKey($token));

        if (! $studentId) {
            throw ValidationException::withMessages([
                'setup_token' => ['This setup link has expired. Enter your student code again.'],
            ]);
        }

        $student = Student::query()
            ->whereKey($studentId)
            ->where('status', 'active')
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'setup_token' => ['This setup link is no longer valid. Enter your student code again.'],
            ]);
        }

        $student->setAppPin($pin);

        return $student->fresh(['branch:id,name,display_name', 'familyGroup']);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeLookup(Student $student): array
    {
        $student->loadMissing(['branch:id,name,display_name']);

        return [
            'student_code' => $student->student_code,
            'name' => $student->name,
            'home_branch' => $student->branch?->display_name ?? $student->branch?->name ?? '',
            'needs_pin_setup' => ! $student->hasAppPin(),
        ];
    }

    public function authenticate(string $studentCode, string $pin): Student
    {
        $student = $this->findActiveStudentByCode($studentCode);

        if (! $student->hasAppPin()) {
            throw ValidationException::withMessages([
                'student_code' => ['Set up your app PIN first using your student code.'],
            ]);
        }

        if (! $student->verifyAppPin($pin)) {
            throw ValidationException::withMessages([
                'student_code' => ['The student code or PIN is incorrect.'],
            ]);
        }

        return $student->load(['branch:id,name,display_name', 'familyGroup']);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeProfile(Student $student): array
    {
        $student->loadMissing(['branch:id,name,display_name', 'familyGroup']);

        $booking = $this->activeBooking($student);
        $seat = $booking?->seat;
        $hall = $seat?->hall;

        $hallLabel = $hall?->name;
        $currentHall = $hallLabel ?? '';

        $expiry = $booking?->plan_expiry_date;
        $daysUntilExpiry = $expiry
            ? (int) Carbon::today()->diffInDays($expiry, false)
            : null;

        $todayAttendance = $this->todayAttendanceRecord($student);

        return [
            'id' => $student->id,
            'student_code' => $student->student_code,
            'name' => $student->name,
            'type' => $student->typeLabel(),
            'email' => $student->effectiveEmail() ?? '',
            'phone' => $student->effectivePhone() ?? '',
            'home_branch' => $student->branch?->display_name ?? $student->branch?->name ?? '',
            'branch_id' => $student->branch_id,
            'branch_name' => $student->branch?->name,
            'current_seat' => $seat?->seat_number ?? '',
            'current_hall' => $currentHall,
            'plan_valid_till' => $expiry?->format('d M Y') ?? '',
            'plan_expiry_date' => $expiry?->toDateString() ?? '',
            'days_until_plan_expiry' => $daysUntilExpiry,
            'seat_expiring_soon' => $daysUntilExpiry !== null && $daysUntilExpiry >= 0 && $daysUntilExpiry <= 7,
            'booked_on' => $booking?->joining_date?->format('d M Y') ?? '',
            'fee_amount' => $booking ? (float) $booking->fee_amount : null,
            'amount_paid' => $booking ? (float) $booking->amount_paid : null,
            'is_checked_in' => $todayAttendance !== null && $todayAttendance->check_out_at === null,
            'checked_in_at' => $todayAttendance?->check_in_at?->format('h:i A') ?? '',
            'avatar_url' => $student->photoUrl() ?? '',
            'has_app_pin' => $student->hasAppPin(),
            'needs_pin_setup' => ! $student->hasAppPin(),
            'payment_history' => $this->paymentHistory($student),
            'family_seats' => $this->familySeats($student),
        ];
    }

    private function todayAttendanceRecord(Student $student): ?AttendanceRecord
    {
        if (! class_exists(AttendanceRecord::class)) {
            return null;
        }

        return AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();
    }

    /**
     * @return list<array{name: string, relationship: string, seat_code: string, hall: string, floor: string, status: string, booked_on: string}>
     */
    private function familySeats(Student $student): array
    {
        if (! $student->family_group_id) {
            return [];
        }

        $today = Carbon::today();

        return $student->linkedSiblings()
            ->with(['bookings' => function ($query) use ($today) {
                $query->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($inner) use ($today) {
                        $inner->whereDate('plan_expiry_date', '>=', $today)
                            ->orWhere('status', 'on_trial');
                    })
                    ->with(['seat.hall'])
                    ->latest('id');
            }])
            ->get()
            ->map(function (Student $sibling) {
                $booking = $sibling->bookings->first();
                $seat = $booking?->seat;
                $hall = $seat?->hall;

                return [
                    'name' => $sibling->name,
                    'relationship' => $sibling->typeLabel(),
                    'seat_code' => $seat?->seat_number ?? '',
                    'hall' => $hall?->name ?? '',
                    'floor' => '',
                    'status' => $booking ? 'active' : 'inactive',
                    'booked_on' => $booking?->joining_date?->format('d M Y') ?? '',
                ];
            })
            ->filter(fn (array $row) => $row['seat_code'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<array{amount: float, amount_label: string, payment_date: string, payment_method: string, reference: string}>
     */
    private function paymentHistory(Student $student): array
    {
        return FeePayment::query()
            ->whereHas('booking', fn ($query) => $query->where('student_id', $student->id))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(static function (FeePayment $payment): array {
                $amount = (float) $payment->amount;

                return [
                    'amount' => $amount,
                    'amount_label' => '₹'.number_format($amount, $amount == floor($amount) ? 0 : 2),
                    'payment_date' => $payment->payment_date?->format('d M Y') ?? '',
                    'payment_method' => $payment->payment_method ?: '—',
                    'reference' => $payment->reference ?? '',
                ];
            })
            ->values()
            ->all();
    }

    private function pinSetupCacheKey(string $token): string
    {
        return 'student_pin_setup:'.hash('sha256', $token);
    }

    private function activeBooking(Student $student): ?SeatBooking
    {
        $today = Carbon::today();

        return $student->bookings()
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($today) {
                $query->whereDate('plan_expiry_date', '>=', $today)
                    ->orWhere('status', 'on_trial');
            })
            ->with(['seat.hall'])
            ->latest('id')
            ->first();
    }
}
