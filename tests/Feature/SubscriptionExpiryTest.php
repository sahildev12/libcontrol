<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SubscriptionExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', false);
        Config::set('libcontrol.subscription.grace_days', 3);
        Config::set('libcontrol.subscription.warn_days', 7);
    }

    private function paidUntil(?string $relative): void
    {
        PlatformSetting::current()->forceFill(['plan_paid_until' => $relative ? now()->modify($relative) : null])->save();
    }

    private function owner(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create(['user_id' => $user->id, 'admin_type' => Admin::TYPE_CLIENT]);

        return $user;
    }

    private function developer(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create(['user_id' => $user->id, 'admin_type' => Admin::TYPE_DEVELOPER]);

        return $user;
    }

    private function staff(): User
    {
        return User::factory()->create(['branch_id' => Branch::factory()->create()->id]);
    }

    public function test_installs_without_a_paid_until_date_are_never_locked(): void
    {
        $this->paidUntil(null);

        $this->actingAs($this->owner())->get(route('students.index'))
            ->assertOk()
            ->assertDontSee('Renewal due')
            ->assertDontSee('Payment due');
    }

    public function test_owner_is_warned_before_the_plan_ends(): void
    {
        $this->paidUntil('+3 days');

        $this->actingAs($this->owner())->get(route('students.index'))
            ->assertOk()
            ->assertSee('Renewal due')
            ->assertSee('Renew now')
            ->assertSee(route('billing.index'), false);
    }

    public function test_grace_period_keeps_the_library_running_with_a_warning(): void
    {
        $this->paidUntil('-1 day');

        $this->actingAs($this->staff())->get(route('students.index'))
            ->assertOk()
            ->assertSee('Payment due')
            ->assertSee('Ask the library owner to renew it.')
            ->assertDontSee('Renew now');
    }

    public function test_owner_is_sent_to_billing_once_the_grace_period_ends(): void
    {
        $this->paidUntil('-4 days');
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('students.index'))->assertRedirect(route('billing.index'));
        $this->actingAs($owner)->post(route('students.store'), [])->assertRedirect(route('billing.index'));
        $this->actingAs($owner)->getJson(route('seats.data'))->assertStatus(402);

        $this->actingAs($owner)->get(route('billing.index'))
            ->assertOk()
            ->assertSee('LibControl is paused for your library')
            ->assertDontSee('Renew now');

        $this->actingAs($owner)->get(route('help-support.index'))->assertOk();
    }

    public function test_branch_staff_get_the_subscription_ended_page(): void
    {
        $this->paidUntil('-4 days');
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertStatus(402)
            ->assertSee('LibControl is paused')
            ->assertSee('How to renew')
            ->assertSee('Log in to renew')
            ->assertSee(route('logout'), false);

        $this->actingAs($staff)->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_staff_can_hand_over_to_the_owner_to_renew(): void
    {
        $this->paidUntil('-4 days');

        $this->actingAs($this->staff())->post(route('logout'), ['renew' => 1])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status');
        $this->assertGuest();

        $owner = $this->owner();
        $owner->forceFill(['password' => bcrypt('secret-pass')])->save();

        $this->post(route('admin.login.store'), ['email' => $owner->email, 'password' => 'secret-pass']);
        $this->assertAuthenticatedAs($owner);

        $this->get(route('dashboard'))->assertRedirect(route('billing.index'));
        $this->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Choose a plan')
            ->assertSee('id="plans"', false)
            ->assertSee('Paying offline');
    }

    public function test_renewal_unlocks_the_library_immediately(): void
    {
        $this->paidUntil('-10 days');
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('billing.index'));

        $this->paidUntil('+1 month');

        $this->actingAs($owner)->get(route('dashboard'))->assertOk();
    }

    public function test_developer_and_public_pages_are_not_locked(): void
    {
        $this->paidUntil('-30 days');

        $this->get(route('login'))->assertOk();
        $response = $this->actingAs($this->developer())->get(route('settings.index'));
        $this->assertNotSame(402, $response->status());
        $this->assertNotSame(route('billing.index'), $response->headers->get('Location'));
    }
}
