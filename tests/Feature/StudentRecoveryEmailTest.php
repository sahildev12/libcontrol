<?php

namespace Tests\Feature;

use App\Mail\StudentNotificationMail;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentRecoveryEmailTest extends TestCase
{
    use RefreshDatabase;

    private Hall $hall;

    private int $seatNumber = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00', 'Asia/Kolkata'));
        PlatformSetting::current()->forceFill(['email_recovery_enabled' => true])->save();
        $this->hall = Hall::factory()->create(['branch_id' => Branch::factory()->create()->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function studentWithPlan(string $email, string $joined, ?string $expires): Student
    {
        $student = Student::factory()->create(['branch_id' => $this->hall->branch_id, 'email' => $email, 'status' => 'active']);

        SeatBooking::create([
            'student_id' => $student->id,
            'seat_id' => Seat::factory()->create(['hall_id' => $this->hall->id, 'seat_number' => ++$this->seatNumber])->id,
            'time_slot' => 'full_day',
            'status' => 'occupied',
            'fee_amount' => 1000,
            'amount_paid' => 1000,
            'joining_date' => $joined,
            'plan_expiry_date' => $expires,
            'fee_type' => 'monthly',
            'payment_plan' => 'full',
        ]);

        return $student;
    }

    public function test_only_students_whose_plan_ended_long_ago_get_one_recovery_email(): void
    {
        Mail::fake();

        $this->studentWithPlan('long-time-member@example.com', '2026-03-01', '2026-12-01');
        $this->studentWithPlan('recently-lapsed@example.com', '2026-08-01', '2026-10-20');
        $this->studentWithPlan('gone@example.com', '2026-05-01', '2026-08-31');
        Student::factory()->create(['branch_id' => $this->hall->branch_id, 'email' => 'never-booked@example.com', 'status' => 'active']);

        $this->artisan('libcontrol:send-recovery-emails')->assertSuccessful();

        Mail::assertSent(StudentNotificationMail::class, 1);
        Mail::assertSent(StudentNotificationMail::class, fn (StudentNotificationMail $mail) => $mail->hasTo('gone@example.com'));

        $this->travel(7)->days();
        $this->artisan('libcontrol:send-recovery-emails')->assertSuccessful();

        Mail::assertSent(StudentNotificationMail::class, 1);
    }
}
