<?php

namespace Tests\Feature;

use App\Mail\AutomationAlertMail;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\FeeInstallment;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\EmailAutomation\FinanceDigestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FinanceDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_finance_digest_sends_to_client_admins_only_with_priority_sections(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-16 08:00:00', 'Asia/Kolkata'));

        PlatformSetting::current()->update([
            'display_name' => 'Sunrise Library',
            'email_finance_digest_enabled' => true,
        ]);

        $branch = Branch::factory()->create();
        $admin = User::factory()->create(['email' => 'owner@example.com', 'branch_id' => $branch->id]);
        Admin::query()->create(['user_id' => $admin->id, 'admin_type' => Admin::TYPE_CLIENT]);

        $staff = User::factory()->create(['email' => 'staff@example.com', 'branch_id' => $branch->id]);

        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $booking = SeatBooking::create([
            'student_id' => $student->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 2000,
            'joining_date' => '2026-10-01',
            'plan_expiry_date' => '2026-12-01',
            'status' => 'active',
        ]);

        FeeInstallment::query()->create([
            'seat_booking_id' => $booking->id,
            'installment_number' => 1,
            'amount' => 2000,
            'due_date' => '2026-11-01',
            'paid_at' => null,
        ]);

        $sent = app(FinanceDigestService::class)->sendWeeklyDigest();

        $this->assertSame(1, $sent);
        Mail::assertSent(AutomationAlertMail::class, 1);
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return str_contains($mail->subjectLine, '[High]')
                && str_contains($mail->subjectLine, 'Weekly finance digest')
                && str_contains($mail->bodyText, 'HIGH PRIORITY')
                && str_contains($mail->bodyText, 'MEDIUM PRIORITY')
                && str_contains($mail->bodyText, 'LOW PRIORITY')
                && str_contains($mail->bodyText, 'IST')
                && $mail->hasTo('owner@example.com');
        });
        Mail::assertNotSent(AutomationAlertMail::class, fn (AutomationAlertMail $mail) => $mail->hasTo('staff@example.com'));

        // Second run same week must not resend.
        $this->assertSame(0, app(FinanceDigestService::class)->sendWeeklyDigest());
        Mail::assertSent(AutomationAlertMail::class, 1);

        unset($staff);
        Carbon::setTestNow();
    }

    public function test_finance_digest_respects_disabled_setting(): void
    {
        Mail::fake();
        PlatformSetting::current()->update(['email_finance_digest_enabled' => false]);

        $user = User::factory()->create(['email' => 'owner@example.com']);
        Admin::query()->create(['user_id' => $user->id, 'admin_type' => Admin::TYPE_CLIENT]);

        $this->assertSame(0, app(FinanceDigestService::class)->sendWeeklyDigest());
        Mail::assertNothingSent();
    }

    public function test_finance_digest_command_is_registered(): void
    {
        $this->assertSame(0, Artisan::call('libcontrol:send-finance-digest', ['--dry-run' => true]));
    }
}
