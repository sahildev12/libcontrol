<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->each(function (Branch $branch) {
            $this->seedBranch($branch);
        });
    }

    private function seedBranch(Branch $branch): void
    {
        $halls = [
            ['name' => 'Computer Lab (F1)', 'description' => 'First floor computer lab', 'capacity' => 40, 'cols' => 8],
            ['name' => 'Library (F1)', 'description' => 'Main reading hall', 'capacity' => 30, 'cols' => 6],
        ];

        $students = collect([
            ['code' => strtoupper(substr($branch->name, 0, 3)).'-001', 'name' => 'Aarav Sharma', 'phone' => '9000000001', 'email' => 'aarav.'.$branch->id.'@example.com'],
            ['code' => strtoupper(substr($branch->name, 0, 3)).'-002', 'name' => 'Meera Patel', 'phone' => '9000000002', 'email' => 'meera.'.$branch->id.'@example.com'],
            ['code' => strtoupper(substr($branch->name, 0, 3)).'-003', 'name' => 'Sahil Khan', 'phone' => '9000000003', 'email' => 'sahil.'.$branch->id.'@example.com'],
            ['code' => strtoupper(substr($branch->name, 0, 3)).'-004', 'name' => 'Priya Singh', 'phone' => '9000000004', 'email' => 'priya.'.$branch->id.'@example.com'],
            ['code' => strtoupper(substr($branch->name, 0, 3)).'-005', 'name' => 'Rohan Das', 'phone' => '9000000005', 'email' => 'rohan.'.$branch->id.'@example.com'],
        ])->map(fn (array $student) => Student::create([
            'branch_id' => $branch->id,
            'student_code' => $student['code'],
            'name' => $student['name'],
            'phone' => $student['phone'],
            'email' => $student['email'],
            'status' => 'active',
        ]));

        $today = Carbon::today();

        foreach ($halls as $hallData) {
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
                        'seat_number' => (string) $seatNumber,
                        'row_number' => $row,
                        'column_number' => $col,
                    ]);

                    $this->maybeAssignBooking($seat, $students, $today, $seatNumber);

                    $seatNumber++;
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Student>  $students
     */
    private function maybeAssignBooking(Seat $seat, $students, Carbon $today, int $seatNumber): void
    {
        $patterns = [
            2 => ['status' => 'occupied', 'expiry' => $today->copy()->addDays(20)],
            4 => ['status' => 'expired', 'expiry' => $today->copy()->subDays(2)],
            6 => ['status' => 'occupied', 'expiry' => $today->copy()->addDays(4)],
            8 => ['status' => 'on_trial', 'expiry' => $today->copy()->addDays(10), 'trial' => true],
            10 => ['status' => 'occupied', 'expiry' => $today->copy()->addDays(45)],
        ];

        if (! isset($patterns[$seatNumber % 12])) {
            return;
        }

        $pattern = $patterns[$seatNumber % 12];
        $student = $students[($seatNumber % $students->count())];

        SeatBooking::create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1500,
            'joining_date' => $today->copy()->subDays(10),
            'plan_expiry_date' => $pattern['expiry'],
            'status' => $pattern['status'],
            'trial_start' => ($pattern['trial'] ?? false) ? $today : null,
            'trial_end' => ($pattern['trial'] ?? false) ? $today->copy()->addDays(2) : null,
        ]);
    }
}
