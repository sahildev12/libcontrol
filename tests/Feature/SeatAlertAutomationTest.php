<?php

namespace Tests\Feature;

use App\Mail\AutomationAlertMail;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\EmailAutomation\SeatAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SeatAlertAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-22 10:00:00', 'Asia/Kolkata'));
        PlatformSetting::current()->forceFill([
            'email_seat_alerts_enabled' => true,
            'seat_vacant_alert_threshold' => 1,
            'seat_high_occupancy_pct' => 90,
        ])->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sends_three_day_and_one_day_expiry_sequence(): void
    {
        Mail::fake();

        $branch = Branch::factory()->create();
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seatA = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 1]);
        $seatB = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 2]);
        $studentA = Student::factory()->create(['branch_id' => $branch->id, 'email' => 'a@example.com']);
        $studentB = Student::factory()->create(['branch_id' => $branch->id, 'email' => 'b@example.com']);

        SeatBooking::create([
            'student_id' => $studentA->id,
            'seat_id' => $seatA->id,
            'time_slot' => 'full_day',
            'status' => 'occupied',
            'fee_amount' => 1000,
            'amount_paid' => 1000,
            'joining_date' => '2026-09-01',
            'plan_expiry_date' => '2026-10-25',
            'fee_type' => 'monthly',
            'payment_plan' => 'full',
        ]);

        SeatBooking::create([
            'student_id' => $studentB->id,
            'seat_id' => $seatB->id,
            'time_slot' => 'full_day',
            'status' => 'occupied',
            'fee_amount' => 1000,
            'amount_paid' => 1000,
            'joining_date' => '2026-09-01',
            'plan_expiry_date' => '2026-10-23',
            'fee_type' => 'monthly',
            'payment_plan' => 'full',
        ]);

        $sent = app(SeatAlertService::class)->sendScheduledAlerts($branch->id);

        $this->assertGreaterThanOrEqual(2, $sent);
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Your Seat Plan Expires in 3 Days';
        });
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Your Seat Plan Expires Tomorrow';
        });
    }

    public function test_booking_confirmed_is_idempotent(): void
    {
        Mail::fake();

        $branch = Branch::factory()->create();
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id, 'email' => 'new@example.com']);

        $booking = SeatBooking::create([
            'student_id' => $student->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'status' => 'occupied',
            'fee_amount' => 1000,
            'amount_paid' => 0,
            'joining_date' => '2026-10-22',
            'plan_expiry_date' => '2026-11-22',
            'fee_type' => 'monthly',
            'payment_plan' => 'full',
        ]);

        $service = app(SeatAlertService::class);
        $this->assertTrue($service->sendBookingConfirmed($booking));
        $this->assertFalse($service->sendBookingConfirmed($booking));
        Mail::assertSent(AutomationAlertMail::class, 1);
    }

    public function test_vacant_seat_digest_emails_owners(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['branch_id' => null, 'email' => 'owner@example.com']);
        Admin::query()->create(['user_id' => $owner->id, 'admin_type' => Admin::TYPE_CLIENT]);

        $branch = Branch::factory()->create();
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        Seat::factory()->count(3)->create(['hall_id' => $hall->id]);

        $sent = app(SeatAlertService::class)->sendScheduledAlerts($branch->id);

        $this->assertGreaterThanOrEqual(1, $sent);
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'You Have Vacant Seats Ready to Fill';
        });
    }
}
