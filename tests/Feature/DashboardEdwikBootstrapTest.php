<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEdwikBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_edwik_create_branch_tip_when_library_has_no_branches(): void
    {
        config(['growth.enabled' => true]);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->withSession(['active_branch_id' => 'all'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lc-ai-banner', false)
            ->assertSee('Create your first branch', false)
            ->assertSee('Create Branch', false)
            ->assertSee(route('branch.index'), false);
    }
}
