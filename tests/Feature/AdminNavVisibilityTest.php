<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_admin_can_see_settings_in_sidebar(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Settings', false);
    }

    public function test_branch_staff_can_see_settings_in_sidebar(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Settings', false);
    }

    public function test_developer_admin_does_not_see_settings_in_sidebar(): void
    {
        \Illuminate\Support\Facades\Config::set('libcontrol.license_server.enabled', true);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $this->actingAs($user)
            ->get(route('developer.deployments.index'))
            ->assertOk()
            ->assertDontSee('>Settings<', false);
    }

    public function test_developer_admin_sees_portal_settings_when_tenancy_enabled(): void
    {
        \Illuminate\Support\Facades\Config::set('libcontrol.license_server.enabled', true);
        \Illuminate\Support\Facades\Config::set('libcontrol.tenancy.enabled', true);
        \Illuminate\Support\Facades\Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $this->actingAs($user)
            ->get(route('developer.deployments.index'))
            ->assertOk()
            ->assertSee('Portal Settings', false);
    }

    public function test_client_admin_does_not_see_developer_tools_in_sidebar(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Dev &amp; Domains', false)
            ->assertDontSee('Client Libraries', false);
    }
}
