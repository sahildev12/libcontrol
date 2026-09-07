<?php

namespace App\Services;

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
            'plan_valid_till' => $booking?->plan_expiry_date?->format('d M Y') ?? '',
            'is_checked_in' => false,
            'checked_in_at' => '',
            'avatar_url' => $student->photoUrl() ?? '',
            'has_app_pin' => $student->hasAppPin(),
            'needs_pin_setup' => ! $student->hasAppPin(),
        ];
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
