<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\User;
use App\Support\Runtime\DeploymentState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeploymentLicenseMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_installation_returns_license_page(): void
    {
        Config::set('libcontrol.license_server.enabled', false);

        Cache::put(DeploymentState::CACHE_KEY, [
            'status' => 'pending',
            'authorized' => false,
            'grace_until' => now()->subDay()->toIso8601String(),
            'checked_at' => now()->toIso8601String(),
        ], now()->addHour());

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden()
            ->assertSee('Installation not licensed');
    }

    public function test_authorized_installation_allows_access(): void
    {
        Config::set('libcontrol.license_server.enabled', false);

        Cache::put(DeploymentState::CACHE_KEY, [
            'status' => 'ok',
            'authorized' => true,
            'grace_until' => null,
            'checked_at' => now()->toIso8601String(),
        ], now()->addHour());

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_license_server_instance_skips_enforcement(): void
    {
        Config::set('libcontrol.license_server.enabled', true);

        Cache::put(DeploymentState::CACHE_KEY, [
            'status' => 'pending',
            'authorized' => false,
            'grace_until' => now()->subDay()->toIso8601String(),
            'checked_at' => now()->toIso8601String(),
        ], now()->addHour());

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
