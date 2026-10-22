<?php

namespace Tests\Feature;

use App\Mail\AutomationAlertMail;
use App\Models\Branch;
use App\Models\EmailAutomationLog;
use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\EmailAutomation\FeeAlertService;
use App\Services\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FeeAlertAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-22 10:00:00', 'Asia/Kolkata'));
        PlatformSetting::current()->forceFill([
            'email_fee_alerts_enabled' => true,
        ])->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{0: SeatBooking, 1: FeeInstallment}
     */
    private function bookingWithInstallment(string $dueDate, ?string $paidAt = null, string $email = 'student@example.com'): array
    {
        $branch = Branch::factory()->create();
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => $email,
            'name' => 'Fee Student',
        ]);

        $booking = SeatBooking::create([
            'student_id' => $student->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'status' => 'active',
            'fee_amount' => 3000,
            'amount_paid' => 0,
            'joining_date' => '2026-10-01',
            'plan_expiry_date' => '2026-12-31',
            'fee_type' => 'monthly',
            'payment_plan' => 'installments',
        ]);

        $installment = FeeInstallment::create([
            'seat_booking_id' => $booking->id,
            'installment_number' => 1,
            'amount' => 1000,
            'due_date' => $dueDate,
            'paid_at' => $paidAt,
        ]);

        return [$booking, $installment];
    }

    public function test_sends_fee_alert_for_due_in_five_days_offset(): void
    {
        Mail::fake();

        [, $installment] = $this->bookingWithInstallment('2026-10-27');

        $sent = app(FeeAlertService::class)->sendScheduledAlerts();

        $this->assertSame(1, $sent);
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Your Library Fee Is Due Soon';
        });
        $this->assertDatabaseHas('email_automation_logs', [
            'template_key' => 'fee_due_in_5_days',
            'related_type' => FeeInstallment::class,
            'related_id' => $installment->id,
        ]);
    }

    public function test_sends_overdue_and_due_today_offsets(): void
    {
        Mail::fake();

        $this->bookingWithInstallment('2026-10-22');
        $this->bookingWithInstallment('2026-10-19', null, 'overdue3@example.com');
        $this->bookingWithInstallment('2026-10-15', null, 'overdue7@example.com');

        $sent = app(FeeAlertService::class)->sendScheduledAlerts();

        $this->assertSame(3, $sent);
        Mail::assertSent(AutomationAlertMail::class, 3);
    }

    public function test_skips_paid_installments(): void
    {
        Mail::fake();

        $this->bookingWithInstallment('2026-10-22', '2026-10-20 09:00:00');

        $sent = app(FeeAlertService::class)->sendScheduledAlerts();

        $this->assertSame(0, $sent);
        Mail::assertNothingSent();
    }

    public function test_idempotent_send_via_automation_log(): void
    {
        Mail::fake();

        [, $installment] = $this->bookingWithInstallment('2026-10-27');

        $service = app(FeeAlertService::class);
        $this->assertSame(1, $service->sendScheduledAlerts());
        $this->assertSame(0, $service->sendScheduledAlerts());

        $this->assertSame(1, EmailAutomationLog::query()
            ->where('template_key', 'fee_due_in_5_days')
            ->where('related_id', $installment->id)
            ->count());
        Mail::assertSent(AutomationAlertMail::class, 1);
    }

    public function test_payment_received_email_is_one_shot(): void
    {
        Mail::fake();

        [$booking] = $this->bookingWithInstallment('2026-10-30');

        $payment = FeePayment::create([
            'seat_booking_id' => $booking->id,
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-10-22',
            'reference' => 'CASH-1',
        ]);

        $service = app(FeeAlertService::class);
        $this->assertTrue($service->sendPaymentReceived($booking, $payment));
        $this->assertFalse($service->sendPaymentReceived($booking, $payment));

        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Payment Received - Thank You!';
        });
        Mail::assertSent(AutomationAlertMail::class, 1);
    }

    public function test_record_payment_triggers_thank_you_mail(): void
    {
        Mail::fake();

        [$booking] = $this->bookingWithInstallment('2026-10-30');

        app(FeeService::class)->recordPayment($booking, 250, [
            'payment_method' => 'upi',
            'payment_date' => '2026-10-22',
            'reference' => 'UPI-99',
        ]);

        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Payment Received - Thank You!';
        });
    }

    public function test_disabled_toggle_skips_all_fee_alerts(): void
    {
        Mail::fake();
        PlatformSetting::current()->forceFill(['email_fee_alerts_enabled' => false])->save();

        $this->bookingWithInstallment('2026-10-22');

        $this->assertSame(0, app(FeeAlertService::class)->sendScheduledAlerts());
        Mail::assertNothingSent();
    }
}
