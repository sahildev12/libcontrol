<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class LandingDemoSeeder extends Seeder
{
    /**
     * Rich demo data for product screenshots and landing page captures.
     */
    public function run(): void
    {
        $tz = config('libspace.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->startOfDay();

        $this->removeNonDemoBranches();

        $branches = [
            [
                'name' => 'Main Library Center',
                'prefix' => 'MLC',
                'contact_person' => 'Rajesh Verma',
                'phone' => '9876543210',
                'email' => 'main@phenomitlibrary.test',
                'address' => '12 MG Road, Pune, Maharashtra 411001',
                'admin' => [
                    'name' => 'Main Center Admin',
                    'email' => 'admin@main.libspace.test',
                ],
                'halls' => [
                    ['name' => 'Reading Hall — Ground Floor', 'description' => 'Bright open study hall with natural light', 'capacity' => 48, 'cols' => 8],
                    ['name' => 'Quiet Zone — First Floor', 'description' => 'Silent zone for focused preparation', 'capacity' => 24, 'cols' => 6],
                ],
                'student_count' => 22,
                'occupancy_ratio' => 0.62,
                'trial_seats' => 5,
                'expired_seats' => 3,
                'expiring_seats' => 6,
            ],
            [
                'name' => 'North Branch Center',
                'prefix' => 'NBR',
                'contact_person' => 'Sunita Desai',
                'phone' => '9123456780',
                'email' => 'north@phenomitlibrary.test',
                'address' => '45 Civil Lines, Nagpur, Maharashtra 440001',
                'admin' => [
                    'name' => 'North Branch Admin',
                    'email' => 'admin@north.libspace.test',
                ],
                'halls' => [
                    ['name' => 'Study Hall A', 'description' => 'Main reading room', 'capacity' => 36, 'cols' => 6],
                ],
                'student_count' => 14,
                'occupancy_ratio' => 0.55,
                'trial_seats' => 3,
                'expired_seats' => 2,
                'expiring_seats' => 4,
            ],
        ];

        foreach ($branches as $config) {
            $this->seedDemoBranch($config, $today);
        }
    }

    private function removeNonDemoBranches(): void
    {
        $keep = ['Main Library Center', 'North Branch Center'];

        Branch::query()
            ->whereNotIn('name', $keep)
            ->pluck('id')
            ->each(fn (int $branchId) => $this->clearBranchData($branchId));

        Branch::query()->whereNotIn('name', $keep)->delete();
    }

    private function clearBranchData(int $branchId): void
    {
        $bookingIds = SeatBooking::query()
            ->whereHas('seat.hall', fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('id');

        if ($bookingIds->isNotEmpty()) {
            FeePayment::query()->whereIn('seat_booking_id', $bookingIds)->delete();
            FeeInstallment::query()->whereIn('seat_booking_id', $bookingIds)->delete();
            SeatBooking::query()->whereIn('id', $bookingIds)->delete();
        }

        $hallIds = Hall::query()->where('branch_id', $branchId)->pluck('id');
        if ($hallIds->isNotEmpty()) {
            Seat::query()->whereIn('hall_id', $hallIds)->delete();
        }

        Hall::query()->where('branch_id', $branchId)->delete();
        Enquiry::query()->where('branch_id', $branchId)->delete();
        Student::query()->where('branch_id', $branchId)->delete();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function seedDemoBranch(array $config, Carbon $today): void
    {
        $branch = Branch::query()->firstOrCreate(
            ['name' => $config['name']],
            [
                'display_name' => $config['name'],
                'contact_person' => $config['contact_person'],
                'phone' => $config['phone'],
                'email' => $config['email'],
                'address' => $config['address'],
                'student_code_prefix' => $config['prefix'],
                'student_code_padding' => 3,
                'expiry_reminder_days' => 7,
                'library_open_time' => '06:00:00',
                'library_close_time' => '22:00:00',
                'is_open_24_hours' => false,
            ],
        );

        $branch->update([
            'display_name' => $config['name'],
            'contact_person' => $config['contact_person'],
            'phone' => $config['phone'],
            'email' => $config['email'],
            'address' => $config['address'],
            'student_code_prefix' => $config['prefix'],
        ]);

        $adminUser = User::query()->updateOrCreate(
            ['email' => $config['admin']['email']],
            [
                'branch_id' => $branch->id,
                'name' => $config['admin']['name'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $this->clearBranchData($branch->id);

        $studentRoster = $this->studentRoster($config['prefix'], (int) $config['student_count']);
        $students = collect();

        foreach ($studentRoster as $index => $studentData) {
            $student = Student::create([
                'branch_id' => $branch->id,
                'student_code' => $studentData['code'],
                'name' => $studentData['name'],
                'gender' => $studentData['gender'],
                'date_of_birth' => $today->copy()->subYears(20 + ($index % 5))->subMonths($index),
                'father_name' => $studentData['father'],
                'preparing_for' => $studentData['preparing_for'],
                'phone' => '9'.str_pad((string) ($branch->id * 10000000 + 1000000 + $index), 9, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '.', $studentData['name'])).'.'.$branch->id.'.demo@example.com',
                'address' => $config['address'],
                'status' => 'active',
                'student_type' => $studentData['trial'] ? Student::TYPE_TRIAL : Student::TYPE_REGULAR,
            ]);

            if ($index < 3) {
                $student->created_at = $today->copy()->subHours(4 - $index);
                $student->save();
            }

            $students->push($student);
        }

        $allSeats = collect();
        foreach ($config['halls'] as $hallData) {
            $hall = Hall::create([
                'branch_id' => $branch->id,
                'name' => $hallData['name'],
                'description' => $hallData['description'],
                'seat_capacity' => $hallData['capacity'],
            ]);

            $cols = $hallData['cols'];
            $rows = (int) ceil($hallData['capacity'] / $cols);
            $seatNumber = 1;

            for ($row = 1; $row <= $rows; $row++) {
                for ($col = 1; $col <= $cols; $col++) {
                    if ($seatNumber > $hallData['capacity']) {
                        break;
                    }

                    $seat = Seat::create([
                        'hall_id' => $hall->id,
                        'seat_number' => str_pad((string) $seatNumber, 2, '0', STR_PAD_LEFT),
                        'row_number' => $row,
                        'column_number' => $col,
                    ]);

                    $allSeats->push($seat);
                    $seatNumber++;
                }
            }
        }

        $totalSeats = $allSeats->count();
        $occupiedTarget = (int) round($totalSeats * $config['occupancy_ratio']);
        $trialTarget = (int) $config['trial_seats'];
        $expiredTarget = (int) $config['expired_seats'];
        $expiringTarget = (int) $config['expiring_seats'];

        $trialStudents = $students->filter(fn (Student $s) => $s->student_type === Student::TYPE_TRIAL)->values();
        if ($trialStudents->isEmpty()) {
            $trialStudents = $students->take($trialTarget)->each(function (Student $student) {
                $student->update(['student_type' => Student::TYPE_TRIAL]);
            });
        }

        $regularStudents = $students->filter(fn (Student $s) => $s->student_type === Student::TYPE_REGULAR)->values();
        $studentIndex = 0;
        $bookings = collect();

        foreach ($allSeats->values() as $position => $seat) {
            $kind = 'vacant';

            if ($position < $occupiedTarget) {
                $kind = 'occupied';
            } elseif ($position < $occupiedTarget + $expiringTarget) {
                $kind = 'expiring';
            } elseif ($position < $occupiedTarget + $expiringTarget + $trialTarget) {
                $kind = 'trial';
            } elseif ($position < $occupiedTarget + $expiringTarget + $trialTarget + $expiredTarget) {
                $kind = 'expired';
            }

            if ($kind === 'vacant') {
                continue;
            }

            if ($kind === 'trial') {
                $student = $trialStudents[$position % max(1, $trialStudents->count())];
            } else {
                $student = $regularStudents[$studentIndex % max(1, $regularStudents->count())];
                $studentIndex++;
            }

            $feeAmount = match ($position % 4) {
                0 => 3500,
                1 => 4500,
                2 => 6000,
                default => 12000,
            };

            $feeType = $feeAmount >= 10000 ? 'quarterly' : 'monthly';
            $joining = $today->copy()->subDays(12 + ($position % 20));
            $expiry = match ($kind) {
                'expiring' => $today->copy()->addDays(2 + ($position % 5)),
                'expired' => $today->copy()->subDays(3 + ($position % 4)),
                'trial' => $today->copy()->addDays(2),
                default => $today->copy()->addDays(18 + ($position % 25)),
            };

            $bookingData = [
                'seat_id' => $seat->id,
                'student_id' => $student->id,
                'time_slot' => 'full_day',
                'fee_type' => $kind === 'trial' ? 'custom' : $feeType,
                'payment_plan' => ($position % 5 === 0 && $kind !== 'trial') ? 'installments' : 'full',
                'installment_frequency' => ($position % 5 === 0 && $kind !== 'trial') ? 'monthly' : null,
                'fee_amount' => $kind === 'trial' ? 200 : $feeAmount,
                'amount_paid' => 0,
                'membership_mode' => 'assigned_seat',
                'joining_date' => $kind === 'trial' ? $today : $joining,
                'plan_expiry_date' => $expiry,
                'status' => $kind === 'trial' ? 'on_trial' : ($kind === 'expired' ? 'expired' : 'occupied'),
                'trial_start' => $kind === 'trial' ? $today : null,
                'trial_end' => $kind === 'trial' ? $today->copy()->addDays(2) : null,
            ];

            $booking = SeatBooking::create($bookingData);
            $bookings->push($booking);

            if ($kind !== 'trial' && $kind !== 'expired') {
                $this->seedPaymentsForBooking($booking, $adminUser, $today, $position);
            }
        }

        $this->seedEnquiries($branch, $today);
        $this->seedHistoricalRevenue($branch, $adminUser, $today);
    }

    private function seedPaymentsForBooking(SeatBooking $booking, User $receiver, Carbon $today, int $position): void
    {
        $feeAmount = (float) $booking->fee_amount;
        $isPartial = $position % 4 === 1;
        $isInstallment = $booking->payment_plan === 'installments';

        if ($isInstallment) {
            $installmentAmount = round($feeAmount / 3, 2);
            foreach ([1, 2, 3] as $number) {
                $due = $booking->joining_date->copy()->addMonths($number - 1);
                FeeInstallment::create([
                    'seat_booking_id' => $booking->id,
                    'installment_number' => $number,
                    'amount' => $installmentAmount,
                    'due_date' => $due,
                    'paid_at' => $number <= 2 ? $due->copy()->addDay() : null,
                ]);
            }

            $paid = $installmentAmount * 2;
            FeePayment::create([
                'seat_booking_id' => $booking->id,
                'amount' => $installmentAmount,
                'payment_method' => 'upi',
                'payment_date' => $booking->joining_date,
                'reference' => 'UPI-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).'A',
                'notes' => 'First installment',
                'received_by' => $receiver->id,
            ]);
            FeePayment::create([
                'seat_booking_id' => $booking->id,
                'amount' => $installmentAmount,
                'payment_method' => 'cash',
                'payment_date' => $booking->joining_date->copy()->addMonth(),
                'reference' => 'RCPT-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).'B',
                'notes' => 'Second installment',
                'received_by' => $receiver->id,
            ]);

            $booking->update(['amount_paid' => $paid]);

            return;
        }

        if ($isPartial) {
            $first = round($feeAmount * 0.45, 2);
            $second = round($feeAmount - $first, 2);

            FeePayment::create([
                'seat_booking_id' => $booking->id,
                'amount' => $first,
                'payment_method' => 'upi',
                'payment_date' => $booking->joining_date,
                'reference' => 'UPI-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                'notes' => 'Partial payment — first tranche',
                'received_by' => $receiver->id,
            ]);
            FeePayment::create([
                'seat_booking_id' => $booking->id,
                'amount' => $second,
                'payment_method' => 'cash',
                'payment_date' => $today,
                'reference' => 'RCPT-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                'notes' => 'Balance received today',
                'received_by' => $receiver->id,
            ]);

            $booking->update(['amount_paid' => $feeAmount]);

            return;
        }

        $paymentDate = $position % 3 === 0 ? $today : $booking->joining_date;
        FeePayment::create([
            'seat_booking_id' => $booking->id,
            'amount' => $feeAmount,
            'payment_method' => $position % 2 === 0 ? 'upi' : 'cash',
            'payment_date' => $paymentDate,
            'reference' => 'PAY-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
            'notes' => 'Plan fee received',
            'received_by' => $receiver->id,
        ]);

        $booking->update(['amount_paid' => $feeAmount]);
    }

    private function seedEnquiries(Branch $branch, Carbon $today): void
    {
        $enquiries = [
            ['name' => 'Karan Malhotra', 'phone' => '9988776655', 'message' => 'Looking for a quiet seat for UPSC prep — 6 months plan.', 'status' => 'new', 'hours_ago' => 1],
            ['name' => 'Neha Gupta', 'phone' => '9876501234', 'message' => 'Need trial seat for 3 days before monthly enrolment.', 'status' => 'contacted', 'hours_ago' => 5],
            ['name' => 'Aditya Rao', 'phone' => '9123409876', 'message' => 'Interested in evening slot — custom hours.', 'status' => 'new', 'hours_ago' => 8],
            ['name' => 'Shreya Kulkarni', 'phone' => '9012345678', 'message' => 'Group enquiry for 4 students — NEET batch.', 'status' => 'contacted', 'hours_ago' => 26],
            ['name' => 'Manish Tiwari', 'phone' => '8899776655', 'message' => 'Fee installment options for quarterly plan?', 'status' => 'converted', 'hours_ago' => 48],
            ['name' => 'Pooja Shetty', 'phone' => '8765432190', 'message' => 'Walk-in visit scheduled for Saturday.', 'status' => 'closed', 'hours_ago' => 72],
        ];

        foreach ($enquiries as $row) {
            $enquiry = Enquiry::create([
                'branch_id' => $branch->id,
                'name' => $row['name'],
                'phone' => $row['phone'],
                'email' => strtolower(str_replace(' ', '.', $row['name'])).'.demo@example.com',
                'message' => $row['message'],
                'status' => $row['status'],
            ]);

            $enquiry->created_at = $today->copy()->subHours($row['hours_ago']);
            $enquiry->updated_at = $enquiry->created_at;
            $enquiry->save();
        }
    }

    private function seedHistoricalRevenue(Branch $branch, User $receiver, Carbon $today): void
    {
        $seat = Seat::query()
            ->whereHas('hall', fn ($q) => $q->where('branch_id', $branch->id))
            ->with('bookings')
            ->first();

        if (! $seat || $seat->bookings->isEmpty()) {
            return;
        }

        $booking = $seat->bookings->first();
        $amounts = [8200, 12400, 9800, 15600, 11200, 13800];

        foreach ($amounts as $monthOffset => $amount) {
            $paymentDate = $today->copy()->subMonthsNoOverflow(5 - $monthOffset)->day(12);

            FeePayment::create([
                'seat_booking_id' => $booking->id,
                'amount' => $amount,
                'payment_method' => 'upi',
                'payment_date' => $paymentDate,
                'reference' => 'REV-'.$branch->id.'-'.$monthOffset,
                'notes' => 'Monthly collection summary',
                'received_by' => $receiver->id,
            ]);
        }
    }

    /**
     * @return list<array{code: string, name: string, gender: string, father: string, preparing_for: string, trial: bool}>
     */
    private function studentRoster(string $prefix, int $count): array
    {
        $pool = [
            ['name' => 'Aarav Sharma', 'gender' => 'male', 'father' => 'Rakesh Sharma', 'preparing_for' => 'UPSC', 'trial' => false],
            ['name' => 'Priya Singh', 'gender' => 'female', 'father' => 'Vikram Singh', 'preparing_for' => 'JEE', 'trial' => false],
            ['name' => 'Rohan Das', 'gender' => 'male', 'father' => 'Amit Das', 'preparing_for' => 'NEET', 'trial' => false],
            ['name' => 'Meera Patel', 'gender' => 'female', 'father' => 'Sanjay Patel', 'preparing_for' => 'CA Foundation', 'trial' => false],
            ['name' => 'Sahil Khan', 'gender' => 'male', 'father' => 'Imran Khan', 'preparing_for' => 'GATE', 'trial' => false],
            ['name' => 'Ananya Reddy', 'gender' => 'female', 'father' => 'Krishna Reddy', 'preparing_for' => 'Banking', 'trial' => false],
            ['name' => 'Vikram Joshi', 'gender' => 'male', 'father' => 'Harish Joshi', 'preparing_for' => 'SSC', 'trial' => false],
            ['name' => 'Kavya Nair', 'gender' => 'female', 'father' => 'Suresh Nair', 'preparing_for' => 'CLAT', 'trial' => false],
            ['name' => 'Arjun Mehta', 'gender' => 'male', 'father' => 'Nitin Mehta', 'preparing_for' => 'GRE', 'trial' => false],
            ['name' => 'Divya Iyer', 'gender' => 'female', 'father' => 'Ramesh Iyer', 'preparing_for' => 'State PSC', 'trial' => false],
            ['name' => 'Ishaan Verma', 'gender' => 'male', 'father' => 'Rajesh Verma', 'preparing_for' => 'UPSC', 'trial' => true],
            ['name' => 'Tanvi Desai', 'gender' => 'female', 'father' => 'Prakash Desai', 'preparing_for' => 'NEET', 'trial' => true],
            ['name' => 'Harsh Agarwal', 'gender' => 'male', 'father' => 'Sunil Agarwal', 'preparing_for' => 'JEE', 'trial' => true],
            ['name' => 'Sneha Bansal', 'gender' => 'female', 'father' => 'Anil Bansal', 'preparing_for' => 'Banking', 'trial' => true],
            ['name' => 'Rahul Choudhary', 'gender' => 'male', 'father' => 'Mahesh Choudhary', 'preparing_for' => 'GATE', 'trial' => false],
            ['name' => 'Nidhi Kapoor', 'gender' => 'female', 'father' => 'Ashok Kapoor', 'preparing_for' => 'CAT', 'trial' => false],
            ['name' => 'Yash Malhotra', 'gender' => 'male', 'father' => 'Deepak Malhotra', 'preparing_for' => 'UPSC', 'trial' => false],
            ['name' => 'Pallavi Ghosh', 'gender' => 'female', 'father' => 'Subhash Ghosh', 'preparing_for' => 'Law Entrance', 'trial' => false],
            ['name' => 'Dev Prakash', 'gender' => 'male', 'father' => 'Ravi Prakash', 'preparing_for' => 'Railways', 'trial' => false],
            ['name' => 'Ritu Saxena', 'gender' => 'female', 'father' => 'Vijay Saxena', 'preparing_for' => 'Defence', 'trial' => false],
            ['name' => 'Om Bhatt', 'gender' => 'male', 'father' => 'Girish Bhatt', 'preparing_for' => 'NEET', 'trial' => false],
            ['name' => 'Aisha Khan', 'gender' => 'female', 'father' => 'Farhan Khan', 'preparing_for' => 'JEE', 'trial' => false],
        ];

        $roster = [];
        for ($i = 0; $i < $count; $i++) {
            $item = $pool[$i % count($pool)];
            $roster[] = [
                'code' => $prefix.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'name' => $item['name'],
                'gender' => $item['gender'],
                'father' => $item['father'],
                'preparing_for' => $item['preparing_for'],
                'trial' => $item['trial'],
            ];
        }

        return $roster;
    }
}
