<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DeploymentCommand;
use App\Models\LicensedDeployment;
use App\Models\User;
use App\Services\DeploymentCommandProcessor;
use App\Services\Developer\DeploymentCommandService;
use App\Services\EnvFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeploymentRemoteManageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
    }

    public function test_developer_can_view_deployment_manage_page(): void
    {
        $deployment = $this->createDeployment();

        $this->actingAs($this->developerAdmin())
            ->get(route('developer.deployments.manage', $deployment))
            ->assertOk()
            ->assertSee('Remote manage')
            ->assertSee($deployment->client_name);
    }

    public function test_developer_can_queue_clear_cache_command(): void
    {
        $deployment = $this->createDeployment();

        $this->actingAs($this->developerAdmin())
            ->post(route('developer.deployments.manage.command', $deployment), [
                'action' => 'clear_cache',
            ])
            ->assertRedirect(route('developer.deployments.manage', $deployment));

        $this->assertDatabaseHas('deployment_commands', [
            'licensed_deployment_id' => $deployment->id,
            'action' => 'clear_cache',
            'status' => DeploymentCommand::STATUS_PENDING,
        ]);
    }

    public function test_sync_returns_pending_commands_for_authorized_deployment(): void
    {
        $licenseKey = LicensedDeployment::generateKey();
        $deployment = LicensedDeployment::query()->create([
            'client_name' => 'Remote Client',
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'allowed_domains' => ['client.test'],
            'grace_days' => 7,
            'active' => true,
        ]);

        app(DeploymentCommandService::class)->queue($deployment, 'force_sync');

        $payload = [
            'domain' => 'client.test',
            'app_url' => 'https://client.test',
            'fingerprint' => hash('sha256', 'client-install'),
            'meta' => ['php' => PHP_VERSION, 'app' => '1.0'],
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            '/api/runtime/sync',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_LICENSE_KEY' => $licenseKey,
                'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, $licenseKey),
            ],
            $body,
        );

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('commands.0.action', 'force_sync');

        $this->assertDatabaseHas('deployment_commands', [
            'licensed_deployment_id' => $deployment->id,
            'action' => 'force_sync',
            'status' => DeploymentCommand::STATUS_SENT,
        ]);
    }

    public function test_discovery_sync_delivers_license_key_update_for_whitelisted_domain(): void
    {
        $licenseKey = LicensedDeployment::generateKey();
        $deployment = LicensedDeployment::query()->create([
            'client_name' => 'Aims',
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'allowed_domains' => ['aims.phenomit.com'],
            'grace_days' => 7,
            'active' => true,
        ]);

        app(DeploymentCommandService::class)->queueLicenseKeyUpdate($deployment, $licenseKey);

        $payload = [
            'domain' => 'aims.phenomit.com',
            'app_url' => 'https://aims.phenomit.com',
            'fingerprint' => hash('sha256', 'aims-install'),
            'meta' => ['php' => PHP_VERSION, 'app' => '1.0'],
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $secret = (string) config('libcontrol.discovery.secret');

        $this->call(
            'POST',
            '/api/runtime/sync',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_LICENSE_KEY' => LicensedDeployment::PLACEHOLDER_LICENSE_KEY,
                'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, $secret),
            ],
            $body,
        )
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('commands.0.action', 'update_license_key')
            ->assertJsonPath('commands.0.payload.license_key', $licenseKey);
    }

    public function test_env_file_service_updates_license_key_line(): void
    {
        $envPath = storage_path('framework/testing-env');
        file_put_contents($envPath, "APP_NAME=Test\nLIBCONTROL_LICENSE_KEY=old_key\n");

        app(EnvFileService::class)->set('LIBCONTROL_LICENSE_KEY', 'ls_newkey', $envPath);

        $this->assertStringContainsString('LIBCONTROL_LICENSE_KEY=ls_newkey', (string) file_get_contents($envPath));

        @unlink($envPath);
    }

    public function test_command_processor_updates_env_license_key(): void
    {
        $licenseKey = LicensedDeployment::generateKey();

        $this->mock(EnvFileService::class, function ($mock) use ($licenseKey): void {
            $mock->shouldReceive('set')
                ->once()
                ->with('LIBCONTROL_LICENSE_KEY', $licenseKey);
        });

        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        $result = app(DeploymentCommandProcessor::class)->process([
            'id' => 'env-test',
            'action' => 'update_license_key',
            'payload' => ['license_key' => $licenseKey],
        ]);

        $this->assertSame('completed', $result['status']);
    }

    public function test_command_processor_executes_clear_cache(): void
    {
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();
        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once();
        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once();

        $result = app(DeploymentCommandProcessor::class)->process([
            'id' => 'test-id',
            'action' => 'clear_cache',
        ]);

        $this->assertSame('completed', $result['status']);
    }

    public function test_sync_records_command_results_from_client(): void
    {
        $licenseKey = LicensedDeployment::generateKey();
        $deployment = LicensedDeployment::query()->create([
            'client_name' => 'Remote Client',
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'allowed_domains' => ['client.test'],
            'grace_days' => 7,
            'active' => true,
        ]);

        $command = app(DeploymentCommandService::class)->queue($deployment, 'force_sync');
        $command->update(['status' => DeploymentCommand::STATUS_SENT, 'sent_at' => now()]);

        $payload = [
            'domain' => 'client.test',
            'app_url' => 'https://client.test',
            'fingerprint' => hash('sha256', 'client-install'),
            'meta' => [
                'php' => PHP_VERSION,
                'app' => '1.0',
                'command_results' => [
                    [
                        'id' => $command->id,
                        'status' => 'completed',
                        'result' => 'Sync acknowledged.',
                    ],
                ],
            ],
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/runtime/sync',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_LICENSE_KEY' => $licenseKey,
                'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, $licenseKey),
            ],
            $body,
        )->assertOk();

        $this->assertDatabaseHas('deployment_commands', [
            'id' => $command->id,
            'status' => DeploymentCommand::STATUS_COMPLETED,
            'result' => 'Sync acknowledged.',
        ]);
    }

    private function createDeployment(): LicensedDeployment
    {
        return LicensedDeployment::query()->create([
            'client_name' => 'North Library',
            'license_key_hash' => LicensedDeployment::hashKey(LicensedDeployment::generateKey()),
            'allowed_domains' => ['north.test'],
            'grace_days' => 7,
            'active' => true,
        ]);
    }

    private function developerAdmin(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        return $user;
    }
}
