<?php

namespace Database\Seeders;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Services\AttendanceService;
use App\Models\Branch;
use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\Addons\AddonRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MobileAppTestSeeder extends Seeder
{
    public const DEFAULT_PIN = '123456';

    public function run(): void
    {
        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->startOfDay();

        $branch = Branch::query()
            ->where('name', 'Main Library Center')
            ->orWhere('student_code_prefix', 'MLC')
            ->orderByRaw("CASE WHEN name = 'Main Library Center' THEN 0 ELSE 1 END")
            ->first();

        if (! $branch) {
            $this->command?->error('No branch found. Run: php artisan libcontrol:seed-demo');

            return;
        }

        $prefix = strtoupper(trim((string) $branch->student_code_prefix));
        if ($prefix === '') {
            $prefix = 'MLC';
            $branch->update(['student_code_prefix' => $prefix, 'student_code_padding' => 3]);
        }

        $receiver = User::query()->where('branch_id', $branch->id)->first()
            ?? User::query()->whereNull('branch_id')->first();

        $personas = $this->personas($prefix, $today);

        foreach ($personas as $persona) {
            $this->upsertPersona($branch, $persona, $today, $receiver);
        }

        $this->printAccountsTable($personas);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function personas(string $prefix, Carbon $today): array
    {
        return [
            [
                'suffix' => '901',
                'name' => 'App Test Active',
                'scenario' => 'Active seat · plan far from expiry · full payment',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 45,
                    'fee_amount' => 5000,
                    'payments' => 'full',
                ],
            ],
            [
                'suffix' => '902',
                'name' => 'App Test 7 Days Left',
                'scenario' => 'Seat expiring in 7 days (marquee)',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 7,
                    'fee_amount' => 4500,
                    'payments' => 'single',
                ],
            ],
            [
                'suffix' => '903',
                'name' => 'App Test Expires Today',
                'scenario' => 'Seat expires today (marquee, 0 days)',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 0,
                    'fee_amount' => 4000,
                    'payments' => 'single',
                ],
            ],
            [
                'suffix' => '904',
                'name' => 'App Test No Seat',
                'scenario' => 'No active seat booking',
                'pin' => self::DEFAULT_PIN,
                'booking' => null,
            ],
            [
                'suffix' => '905',
                'name' => 'App Test Trial',
                'scenario' => 'Trial seat (on_trial)',
                'pin' => self::DEFAULT_PIN,
                'student_type' => Student::TYPE_TRIAL,
                'booking' => [
                    'status' => 'on_trial',
                    'expiry_days' => 2,
                    'fee_amount' => 200,
                    'payments' => 'none',
                ],
            ],
            [
                'suffix' => '906',
                'name' => 'App Test Pin Setup',
                'scenario' => 'No app PIN yet (first-time setup flow)',
                'pin' => null,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 30,
                    'fee_amount' => 3500,
                    'payments' => 'none',
                ],
            ],
            [
                'suffix' => '907',
                'name' => 'App Test Pay History',
                'scenario' => 'Multiple payments in history',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 60,
                    'fee_amount' => 6000,
                    'payments' => 'multi',
                ],
            ],
            [
                'suffix' => '908',
                'name' => 'App Test Partial Pay',
                'scenario' => 'Active seat · partial fee paid',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 25,
                    'fee_amount' => 5000,
                    'payments' => 'partial',
                ],
            ],
            [
                'suffix' => '909',
                'name' => 'App Test Expired Plan',
                'scenario' => 'Expired booking only (no active seat in app)',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'expired',
                    'expiry_days' => -7,
                    'fee_amount' => 3500,
                    'payments' => 'single',
                ],
            ],
            [
                'suffix' => '910',
                'name' => 'App Test Check Out',
                'scenario' => 'Checked in today — use app/QR to check OUT',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 30,
                    'fee_amount' => 3500,
                    'payments' => 'single',
                ],
                'attendance' => 'checked_in',
            ],
            [
                'suffix' => '911',
                'name' => 'App Test Checked Out',
                'scenario' => 'Already checked in and out today',
                'pin' => self::DEFAULT_PIN,
                'booking' => [
                    'status' => 'occupied',
                    'expiry_days' => 30,
                    'fee_amount' => 3500,
                    'payments' => 'single',
                ],
                'attendance' => 'checked_out',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $persona
     */
    private function upsertPersona(Branch $branch, array $persona, Carbon $today, ?User $receiver): void
    {
        $code = $persona['suffix'];
        $prefix = strtoupper(trim((string) $branch->student_code_prefix));
        $studentCode = $prefix.'-'.$code;

        $phone = '9876543'.$persona['suffix'];

        $student = Student::query()->updateOrCreate(
            ['student_code' => $studentCode],
            [
                'branch_id' => $branch->id,
                'name' => $persona['name'],
                'gender' => 'male',
                'date_of_birth' => $today->copy()->subYears(21),
                'father_name' => 'Demo Parent',
                'phone' => $phone,
                'email' => strtolower(str_replace(' ', '.', $persona['name'])).'@mobile-test.example.com',
                'address' => $branch->address,
                'status' => 'active',
                'student_type' => $persona['student_type'] ?? Student::TYPE_REGULAR,
            ],
        );

        if ($persona['pin']) {
            $student->setAppPin($persona['pin']);
        } else {
            $student->clearAppPin();
        }

        $this->clearStudentBookings($student);

        $bookingConfig = $persona['booking'] ?? null;
        if (! $bookingConfig) {
            $this->seedAttendance($student, $persona['attendance'] ?? null, $today);

            return;
        }

        $seat = $this->vacantSeat($branch);
        if (! $seat) {
            $this->command?->warn("No vacant seat for {$studentCode}; skipped booking.");

            return;
        }

        $expiry = $today->copy()->addDays((int) $bookingConfig['expiry_days']);
        $joining = $today->copy()->subDays(14);

        $booking = SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => ($bookingConfig['status'] ?? '') === 'on_trial' ? 'custom' : 'monthly',
            'payment_plan' => 'full',
            'fee_amount' => $bookingConfig['fee_amount'],
            'amount_paid' => 0,
            'membership_mode' => 'assigned_seat',
            'joining_date' => ($bookingConfig['status'] ?? '') === 'on_trial' ? $today : $joining,
            'plan_expiry_date' => $expiry,
            'status' => $bookingConfig['status'],
            'trial_start' => ($bookingConfig['status'] ?? '') === 'on_trial' ? $today : null,
            'trial_end' => ($bookingConfig['status'] ?? '') === 'on_trial' ? $today->copy()->addDays(2) : null,
        ]);

        $this->seedPayments($booking, $bookingConfig['payments'], $receiver, $today);
        $this->seedAttendance($student, $persona['attendance'] ?? null, $today);
    }

    private function clearStudentBookings(Student $student): void
    {
        $ids = SeatBooking::query()->where('student_id', $student->id)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        FeePayment::query()->whereIn('seat_booking_id', $ids)->delete();
        FeeInstallment::query()->whereIn('seat_booking_id', $ids)->delete();
        SeatBooking::query()->whereIn('id', $ids)->delete();
    }

    private function seedAttendance(Student $student, ?string $mode, Carbon $today): void
    {
        if (! $mode) {
            return;
        }

        try {
            $registry = app(AddonRegistry::class);
            if (! $registry->isInstalled('attendance')) {
                $registry->install('attendance');
            } elseif (! $registry->isEnabled('attendance')) {
                $registry->enable('attendance');
            }
        } catch (\Throwable) {
            return;
        }

        $attendance = app(AttendanceService::class);
        $checkInAt = $today->copy()->setTime(9, 15);

        AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', $today->toDateString())
            ->delete();

        if ($mode === 'checked_in') {
            $attendance->checkInStudent(
                $student,
                AttendanceRecord::METHOD_STUDENT_QR,
                meta: ['seed' => 'mobile_app_test'],
                at: $checkInAt,
            );

            return;
        }

        if ($mode === 'checked_out') {
            $attendance->checkInStudent(
                $student,
                AttendanceRecord::METHOD_STUDENT_QR,
                meta: ['seed' => 'mobile_app_test'],
                at: $checkInAt,
            );
            $attendance->checkOutStudent(
                $student,
                meta: ['seed' => 'mobile_app_test'],
                at: $checkInAt->copy()->addHours(4),
            );
        }
    }

    private function vacantSeat(Branch $branch): ?Seat
    {
        $today = Carbon::today();

        return Seat::query()
            ->whereHas('hall', fn ($q) => $q->where('branch_id', $branch->id))
            ->whereDoesntHave('bookings', function ($q) use ($today) {
                $q->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($inner) use ($today) {
                        $inner->whereDate('plan_expiry_date', '>=', $today)
                            ->orWhere('status', 'on_trial');
                    });
            })
            ->orderBy('id')
            ->first();
    }

    private function seedPayments(SeatBooking $booking, string $mode, ?User $receiver, Carbon $today): void
    {
        if ($mode === 'none' || ! $receiver) {
            return;
        }

        $fee = (float) $booking->fee_amount;

        if ($mode === 'full' || $mode === 'single') {
            FeePayment::query()->create([
                'seat_booking_id' => $booking->id,
                'amount' => $fee,
                'payment_method' => 'upi',
                'payment_date' => $booking->joining_date,
                'reference' => 'MOB-'.$booking->id,
                'notes' => 'Mobile test seed',
                'received_by' => $receiver->id,
            ]);
            $booking->update(['amount_paid' => $fee]);

            return;
        }

        if ($mode === 'partial') {
            $first = round($fee * 0.4, 2);
            FeePayment::query()->create([
                'seat_booking_id' => $booking->id,
                'amount' => $first,
                'payment_method' => 'cash',
                'payment_date' => $booking->joining_date,
                'reference' => 'MOB-P1-'.$booking->id,
                'received_by' => $receiver->id,
            ]);
            $booking->update(['amount_paid' => $first]);

            return;
        }

        if ($mode === 'multi') {
            $chunks = [2000, 2000, 2000];
            $paid = 0.0;
            foreach ($chunks as $i => $amount) {
                FeePayment::query()->create([
                    'seat_booking_id' => $booking->id,
                    'amount' => $amount,
                    'payment_method' => $i % 2 === 0 ? 'upi' : 'cash',
                    'payment_date' => $booking->joining_date->copy()->addDays($i * 10),
                    'reference' => 'MOB-M'.($i + 1).'-'.$booking->id,
                    'received_by' => $receiver->id,
                ]);
                $paid += $amount;
            }
            $booking->update(['amount_paid' => $paid]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $personas
     */
    private function printAccountsTable(array $personas): void
    {
        $prefix = strtoupper(trim((string) (Branch::query()->where('name', 'Main Library Center')->value('student_code_prefix') ?: 'MLC')));

        $rows = [];
        foreach ($personas as $persona) {
            $code = $prefix.'-'.$persona['suffix'];
            $phone = '9876543'.$persona['suffix'];
            $rows[] = [
                $code,
                $persona['scenario'],
                $persona['pin'] ?? '(set PIN on first login)',
                $phone,
            ];
        }

        $this->command?->newLine();
        $this->command?->info('Mobile app test accounts (Main Library Center / '.$prefix.')');
        $this->command?->table(['Student code', 'Scenario', 'App PIN', 'Phone (QR check-in)'], $rows);
        $this->command?->line('Default PIN for most accounts: '.self::DEFAULT_PIN);
        $this->command?->line('Connect app with your library code, then log in with each student code above.');
    }
}
