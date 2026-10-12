<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanLimitUpgradeBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_page_shows_upgrade_banner_when_branch_cap_reached(): void
    {
        PlatformSetting::query()->delete();
        PlatformSetting::query()->create([
            'plan_tier' => 'pro',
            'student_code_padding' => 3,
        ]);

        Branch::factory()->count(2)->create();

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('branch.index'))
            ->assertOk()
            ->assertSee('Plan limit reached', false)
            ->assertSee('Upgrade plan', false)
            ->assertSee(route('billing.index'), false)
            ->assertDontSee('WhatsApp Katsindia', false);
    }

    public function test_halls_page_shows_upgrade_banner_when_hall_cap_reached(): void
    {
        PlatformSetting::query()->delete();
        PlatformSetting::query()->create([
            'plan_tier' => 'starter',
            'student_code_padding' => 3,
        ]);

        $branch = Branch::factory()->create();
        Hall::factory()->create([
            'branch_id' => $branch->id,
            'seat_capacity' => 10,
        ]);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $html = $this->actingAs($user)
            ->get(route('halls.index').'?branch='.$branch->id)
            ->assertOk()
            ->assertSee('Plan limit reached', false)
            ->assertDontSee('Plan: ', false)
            ->getContent();

        $this->assertSame(1, substr_count(strtolower($html), 'plan limit reached'), 'The plan limit notice must appear once per page.');
    }

    public function test_branch_staff_sees_global_banner_on_dashboard_when_seat_cap_reached(): void
    {
        PlatformSetting::query()->delete();
        PlatformSetting::query()->create([
            'plan_tier' => 'starter',
            'student_code_padding' => 3,
        ]);

        $branch = Branch::factory()->create();
        Hall::factory()->create([
            'branch_id' => $branch->id,
            'seat_capacity' => 35,
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Plan limit reached', false)
            ->assertSee('Ask the library owner to upgrade.', false)
            ->assertDontSee('Upgrade plan', false);
    }

    private function cappedStaff(): User
    {
        PlatformSetting::query()->delete();
        PlatformSetting::query()->create([
            'plan_tier' => 'starter',
            'student_code_padding' => 3,
        ]);

        $branch = Branch::factory()->create();
        Hall::factory()->create([
            'branch_id' => $branch->id,
            'seat_capacity' => 35,
        ]);

        return User::factory()->create(['branch_id' => $branch->id]);
    }

    public function test_subscription_notice_replaces_the_plan_limit_notice(): void
    {
        config(['libcontrol.license_server.enabled' => false]);
        $user = $this->cappedStaff();
        PlatformSetting::current()->forceFill(['plan_paid_until' => now()->subDay()])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your subscription has ended', false)
            ->assertDontSee('Plan limit reached', false);
    }

    public function test_closed_plan_limit_notice_returns_after_three_hours_or_next_login(): void
    {
        $user = $this->cappedStaff();

        $this->actingAs($user)->post(route('plan-limit.dismiss'))->assertNoContent();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Plan limit reached', false);

        $this->travel(3)->hours();
        $this->get(route('dashboard'))->assertOk()->assertSee('Plan limit reached', false);

        $this->post(route('plan-limit.dismiss'))->assertNoContent();
        $this->get(route('dashboard'))->assertDontSee('Plan limit reached', false);

        $user->forceFill(['password' => bcrypt('secret-pass')])->save();
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-pass']);
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk()->assertSee('Plan limit reached', false);
    }
}
