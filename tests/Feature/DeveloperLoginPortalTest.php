<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Support\InstallState;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeveloperLoginPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_hub_exposes_developer_login_route(): void
    {
        Config::set('libcontrol.license_server.enabled', true);

        $this->get(route('developer.login'))
            ->assertOk()
            ->assertSee('Developer login', false);
    }

    public function test_install_state_distinguishes_hub_and_client(): void
    {
        Config::set('libcontrol.license_server.enabled', false);
        $this->assertFalse(InstallState::isHub());

        Config::set('libcontrol.license_server.enabled', true);
        $this->assertTrue(InstallState::isHub());
    }

    public function test_developer_admin_must_use_developer_login(): void
    {
        Config::set('libcontrol.license_server.enabled', true);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_developer_admin_can_authenticate_on_developer_login(): void
    {
        Config::set('libcontrol.license_server.enabled', true);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $response = $this->post('/developer/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('developer.deployments.index', absolute: false));
    }

    public function test_developer_logout_returns_to_developer_login(): void
    {
        Config::set('libcontrol.license_server.enabled', true);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['login_portal' => 'developer'])
            ->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('developer.login', absolute: false));
    }
}
