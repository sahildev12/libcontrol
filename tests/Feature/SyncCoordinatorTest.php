<?php

namespace Tests\Feature;

use App\Models\LicensedDeployment;
use App\Models\LicensedDeployment;
use App\Support\Runtime\DeploymentState;
use App\Support\Runtime\SyncCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_stores_authorized_state_from_api_response(): void
    {
        Config::set('libspace.license_server.enabled', false);
        Config::set('libspace.deployment.license_key', 'ls_test_key');
        Config::set('libspace.deployment.sync_endpoint', 'https://sync.test/ping');

        Http::fake([
            'https://sync.test/ping' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        app(SyncCoordinator::class)->sync();

        $state = app(DeploymentState::class)->current();

        $this->assertTrue($state['authorized']);
        $this->assertSame('ok', $state['status']);
    }

    public function test_sync_stores_pending_state_for_unauthorized_domain(): void
    {
        Config::set('libspace.license_server.enabled', false);
        Config::set('libspace.deployment.license_key', 'ls_test_key');
        Config::set('libspace.deployment.sync_endpoint', 'https://sync.test/ping');

        Http::fake([
            'https://sync.test/ping' => Http::response([
                'status' => 'pending',
                'grace_until' => now()->addDays(3)->toIso8601String(),
            ], 200),
        ]);

        app(SyncCoordinator::class)->sync();

        $state = app(DeploymentState::class)->current();

        $this->assertFalse($state['authorized']);
        $this->assertSame('pending', $state['status']);
        $this->assertNotEmpty($state['grace_until']);
    }

    public function test_discovery_sync_runs_without_license_key(): void
    {
        Config::set('libspace.license_server.enabled', false);
        Config::set('libspace.deployment.license_key', LicensedDeployment::PLACEHOLDER_LICENSE_KEY);
        Config::set('libspace.deployment.sync_endpoint', 'https://sync.test/ping');
        Config::set('libspace.discovery.secret', 'test-discovery-secret');

        Http::fake([
            'https://sync.test/ping' => Http::response([
                'status' => 'pending',
                'grace_until' => now()->addDays(7)->toIso8601String(),
            ], 200),
        ]);

        app(SyncCoordinator::class)->sync();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sync.test/ping'
                && $request->hasHeader('X-Sync-Token')
                && ! $request->hasHeader('X-License-Key');
        });
    }
}
