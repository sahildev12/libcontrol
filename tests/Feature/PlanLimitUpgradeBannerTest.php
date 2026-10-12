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
            ->assertSee('LibControl plan limit reached', false)
            ->assertSee('View LibControl plans', false)
            ->assertSee('WhatsApp Katsindia', false);
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

        $this->actingAs($user)
            ->get(route('halls.index').'?branch='.$branch->id)
            ->assertOk()
            ->assertSee('LibControl plan limit reached', false)
            ->assertSee('for halls or total seats', false);
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
            ->assertSee('LibControl plan limit reached', false)
            ->assertSee('Ask the library owner', false)
            ->assertDontSee('View LibControl plans', false);
    }
}
