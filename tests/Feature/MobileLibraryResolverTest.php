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
            'library_code' => '482913',
            'domain' => 'library.dise.org.in',
            'app_url' => 'https://library.dise.org.in',
            'client_name' => 'Dise Library',
            'last_seen_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/mobile/libraries/482913');

        $response->assertOk()
            ->assertJson([
                'code' => '482913',
                'api_base_url' => 'https://library.dise.org.in',
                'name' => 'Dise Library',
            ]);
    }

    public function test_resolver_returns_404_for_unknown_code(): void
    {
        $this->getJson('/api/v1/mobile/libraries/999999')
            ->assertNotFound();
    }

    public function test_resolver_strips_non_digits_from_input(): void
    {
        LibraryRegistry::query()->create([
            'library_code' => '482913',
            'domain' => 'library.example.com',
            'app_url' => 'https://library.example.com',
            'client_name' => 'Example Library',
            'last_seen_at' => now(),
        ]);

        $this->getJson('/api/v1/mobile/libraries/482-913')
            ->assertOk()
            ->assertJson([
                'code' => '482913',
                'api_base_url' => 'https://library.example.com',
            ]);
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
                'library_code' => '482913',
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
            'library_code' => '482913',
            'domain' => 'library.dise.org.in',
            'app_url' => 'https://library.dise.org.in',
            'client_name' => 'Dise Library',
        ]);
    }
}
