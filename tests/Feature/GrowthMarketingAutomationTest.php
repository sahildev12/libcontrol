<?php

namespace Tests\Feature;

use App\Mail\AutomationAlertMail;
use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\EmailAutomation\GrowthCampaignService;
use App\Services\EmailAutomation\MarketingCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GrowthMarketingAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-22 10:00:00', 'Asia/Kolkata'));

        $owner = User::factory()->create(['branch_id' => null, 'email' => 'owner@example.com']);
        Admin::query()->create(['user_id' => $owner->id, 'admin_type' => Admin::TYPE_CLIENT]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_growth_campaign_requires_opt_in(): void
    {
        Mail::fake();
        PlatformSetting::current()->forceFill([
            'email_growth_enabled' => false,
            'growth_campaign_step' => 0,
            'plan_paid_until' => '2027-01-01',
        ])->save();

        $this->assertSame(0, app(GrowthCampaignService::class)->sendScheduledCampaign());
        Mail::assertNothingSent();
    }

    public function test_growth_campaign_sends_and_advances_step(): void
    {
        Mail::fake();
        PlatformSetting::current()->forceFill([
            'email_growth_enabled' => true,
            'growth_campaign_step' => 0,
            'growth_last_sent_at' => null,
            'plan_paid_until' => '2027-01-01',
        ])->save();

        $this->assertSame(1, app(GrowthCampaignService::class)->sendScheduledCampaign());
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return str_contains($mail->subjectLine, 'Fill Empty Seats');
        });

        $settings = PlatformSetting::current()->fresh();
        $this->assertSame(1, (int) $settings->growth_campaign_step);
        $this->assertNotNull($settings->growth_last_sent_at);
    }

    public function test_marketing_requires_opt_in_and_unsubscribe_works(): void
    {
        Mail::fake();
        $token = 'unsubtoken1234567890abcdefghijklmnop';

        PlatformSetting::current()->forceFill([
            'email_marketing_enabled' => true,
            'marketing_unsubscribe_token' => $token,
        ])->save();

        $this->assertSame(1, app(MarketingCampaignService::class)->sendScheduledCampaign());
        Mail::assertSent(AutomationAlertMail::class, 1);

        $this->get(route('email.unsubscribe', ['token' => $token]))
            ->assertOk()
            ->assertSee('Unsubscribed');

        $fresh = PlatformSetting::current()->fresh();
        $this->assertFalse((bool) $fresh->email_marketing_enabled);
        $this->assertFalse((bool) $fresh->email_growth_enabled);

        Mail::fake();
        $this->assertSame(0, app(MarketingCampaignService::class)->sendScheduledCampaign());
        Mail::assertNothingSent();
    }
}
