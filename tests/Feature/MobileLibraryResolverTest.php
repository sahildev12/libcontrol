<?php

namespace Tests\Feature;

use App\Models\LibraryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MobileLibraryResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
    }

    public function test_resolver_returns_library_details_by_code(): void
    {
        LibraryRegistry::query()->create([
            'student_code_prefix' => 'DISE',
            'domain' => 'library.dise.org.in',
            'app_url' => 'https://library.dise.org.in',
            'client_name' => 'Dise Library',
            'last_seen_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/mobile/libraries/DISE');

        $response->assertOk()
            ->assertJson([
                'code' => 'DISE',
                'api_base_url' => 'https://library.dise.org.in',
                'name' => 'Dise Library',
            ]);
    }

    public function test_resolver_returns_404_for_unknown_code(): void
    {
        $this->getJson('/api/v1/mobile/libraries/UNKNOWN')
            ->assertNotFound();
    }

    public function test_runtime_sync_updates_library_registry(): void
    {
        Config::set('libcontrol.discovery.secret', 'test-discovery-secret');

        $payload = [
            'domain' => 'library.dise.org.in',
            'app_url' => 'https://library.dise.org.in',
            'fingerprint' => hash('sha256', 'install-dise'),
            'meta' => [
                'php' => PHP_VERSION,
                'app' => '2.1.3',
                'student_code_prefix' => 'DISE',
                'client_name' => 'Dise Library',
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
                'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, 'test-discovery-secret'),
            ],
            $body,
        )->assertOk();

        $this->assertDatabaseHas('library_registry', [
            'student_code_prefix' => 'DISE',
            'domain' => 'library.dise.org.in',
            'app_url' => 'https://library.dise.org.in',
            'client_name' => 'Dise Library',
        ]);
    }
}
