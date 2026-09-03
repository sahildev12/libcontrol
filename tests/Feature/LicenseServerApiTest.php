<?php

namespace Tests\Feature;

use App\Models\LicensedDeployment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LicenseServerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
    }

    public function test_authorized_domain_returns_ok_status(): void
    {
        $licenseKey = LicensedDeployment::generateKey();

        LicensedDeployment::query()->create([
            'client_name' => 'City Library',
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'allowed_domains' => ['library.test'],
            'grace_days' => 7,
            'active' => true,
        ]);

        $payload = [
            'domain' => 'library.test',
            'app_url' => 'https://library.test',
            'fingerprint' => hash('sha256', 'install-a'),
            'meta' => ['php' => PHP_VERSION, 'app' => '1.0'],
        ];

        $response = $this->postSync($licenseKey, $payload);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('installation_events', [
            'domain' => 'library.test',
            'fingerprint' => hash('sha256', 'install-a'),
            'is_authorized' => 1,
        ]);
    }

    public function test_wrong_domain_returns_pending_with_grace(): void
    {
        $licenseKey = LicensedDeployment::generateKey();

        LicensedDeployment::query()->create([
            'client_name' => 'City Library',
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'allowed_domains' => ['library.test'],
            'grace_days' => 5,
            'active' => true,
        ]);

        $payload = [
            'domain' => 'pirate.test',
            'app_url' => 'https://pirate.test',
            'fingerprint' => hash('sha256', 'install-b'),
            'meta' => ['php' => PHP_VERSION, 'app' => '1.0'],
        ];

        $response = $this->postSync($licenseKey, $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonStructure(['grace_until']);

        $this->assertDatabaseHas('installation_events', [
            'domain' => 'pirate.test',
            'is_authorized' => 0,
        ]);
    }

    public function test_unknown_license_key_is_logged_as_unauthorized(): void
    {
        $licenseKey = LicensedDeployment::generateKey();

        $payload = [
            'domain' => 'unknown.test',
            'app_url' => 'https://unknown.test',
            'fingerprint' => hash('sha256', 'install-c'),
            'meta' => ['php' => PHP_VERSION, 'app' => '1.0'],
        ];

        $response = $this->postSync($licenseKey, $payload);

        $response->assertOk()->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('installation_events', [
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'domain' => 'unknown.test',
            'is_authorized' => 0,
        ]);
    }

    public function test_discovery_ping_without_license_key_is_logged(): void
    {
        Config::set('libcontrol.discovery.secret', 'test-discovery-secret');

        $payload = [
            'domain' => 'library.dise.org.in',
            'app_url' => 'https://library.dise.org.in',
            'fingerprint' => hash('sha256', 'install-discovery'),
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
                'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, 'test-discovery-secret'),
            ],
            $body,
        );

        $response->assertOk()->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('installation_events', [
            'license_key_hash' => LicensedDeployment::discoveryKeyHash(),
            'domain' => 'library.dise.org.in',
            'is_authorized' => 0,
        ]);
    }

    public function test_sync_endpoint_does_not_require_csrf_token(): void
    {
        Config::set('libcontrol.discovery.secret', 'test-discovery-secret');

        $payload = [
            'domain' => 'csrf-free.test',
            'app_url' => 'https://csrf-free.test',
            'fingerprint' => hash('sha256', 'csrf-free'),
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
                'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, 'test-discovery-secret'),
            ],
            $body,
        );

        $response->assertOk()->assertJsonPath('status', 'pending');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSync(string $licenseKey, array $payload)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call(
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
    }
}
