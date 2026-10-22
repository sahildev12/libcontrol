<?php

namespace Tests\Feature;

use App\Mail\AutomationAlertMail;
use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\EmailAutomation\SubscriptionTrialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionTrialAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-22 10:00:00', 'Asia/Kolkata'));
        config(['libcontrol.license_server.enabled' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sends_trial_day_and_pre_expiry_emails(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['branch_id' => null, 'email' => 'owner@example.com']);
        Admin::query()->create(['user_id' => $owner->id, 'admin_type' => Admin::TYPE_CLIENT]);

        PlatformSetting::current()->forceFill([
            'email_subscription_alerts_enabled' => true,
            'plan_tier' => 'starter',
            'subscription_trial_started_at' => '2026-10-22 00:00:00',
            'plan_paid_until' => '2026-11-30 23:59:59',
        ])->save();

        $sent = app(SubscriptionTrialService::class)->sendScheduledAlerts();

        $this->assertSame(1, $sent);
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Welcome to Your LibControl Trial';
        });
    }

    public function test_skips_paid_pro_with_future_paid_until(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['branch_id' => null, 'email' => 'owner@example.com']);
        Admin::query()->create(['user_id' => $owner->id, 'admin_type' => Admin::TYPE_CLIENT]);

        PlatformSetting::current()->forceFill([
            'email_subscription_alerts_enabled' => true,
            'plan_tier' => 'pro',
            'subscription_trial_started_at' => '2026-10-22 00:00:00',
            'plan_paid_until' => '2027-01-01 00:00:00',
        ])->save();

        $this->assertSame(0, app(SubscriptionTrialService::class)->sendScheduledAlerts());
        Mail::assertNothingSent();
    }

    public function test_sends_three_days_before_expiry(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['branch_id' => null, 'email' => 'owner@example.com']);
        Admin::query()->create(['user_id' => $owner->id, 'admin_type' => Admin::TYPE_CLIENT]);

        PlatformSetting::current()->forceFill([
            'email_subscription_alerts_enabled' => true,
            'plan_tier' => 'starter',
            'subscription_trial_started_at' => '2026-10-01 00:00:00',
            'plan_paid_until' => '2026-10-25 00:00:00',
        ])->save();

        $sent = app(SubscriptionTrialService::class)->sendScheduledAlerts();

        $this->assertSame(1, $sent);
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Your LibControl Trial Ends in 3 Days';
        });
    }
}
