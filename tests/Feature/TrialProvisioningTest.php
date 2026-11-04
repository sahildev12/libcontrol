<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Hosting\HostingerProvider;
use App\Services\Hosting\HostingProvider;
use App\Services\Tenancy\TenantConnectionManager;
use App\Services\Tenancy\TrialProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Support\FakeHostingProvider;
use Tests\TestCase;

class TrialProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private FakeHostingProvider $hosting;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.tenancy.base_domain', 'libcontrol.in');
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');

        $this->hosting = new FakeHostingProvider;
        $this->app->instance(HostingProvider::class, $this->hosting);
    }

    protected function tearDown(): void
    {
        foreach (File::glob(database_path('lc_trial_*.sqlite')) as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    private function trials(): TrialProvisioner
    {
        $trials = $this->app->make(TrialProvisioner::class);
        $trials->setSleeper(static fn () => null);

        return $trials;
    }

    private function registerSunrise(TrialProvisioner $trials): Tenant
    {
        return $trials->register([
            'library_name' => 'Sunrise Library',
            'subdomain' => 'Sunrise',
            'owner_name' => 'Asha',
            'owner_email' => 'Asha@Example.com',
            'password' => 'secret-pass-1',
            'source' => 'email',
        ]);
    }

    public function test_trial_is_provisioned_with_its_own_database_and_owner_login(): void
    {
        $trials = $this->trials();
        $tenant = $trials->provision($this->registerSunrise($trials));

        $this->assertSame(Tenant::STATUS_READY, $tenant->status, (string) $tenant->provision_error);
        $this->assertTrue($tenant->active);
        $this->assertTrue($tenant->is_trial);
        $this->assertSame('pro', $tenant->plan_tier);
        $this->assertSame('sunrise', $tenant->subdomain);
        $this->assertSame(['sunrise'], $this->hosting->subdomains);
        $this->assertNull($tenant->owner_password_hash);
        $this->assertNotNull($tenant->provisioned_at);
        $this->assertTrue($tenant->trial_ends_at->between(now()->addDays(14), now()->addDays(16)));

        $this->app->make(TenantConnectionManager::class)->runOnTenant($tenant, function () {
            $user = User::query()->where('email', 'asha@example.com')->firstOrFail();

            $this->assertTrue(Hash::check('secret-pass-1', $user->password));
            $this->assertNull($user->email_verified_at);
            $this->assertTrue(Admin::query()->where('user_id', $user->id)->where('admin_type', Admin::TYPE_CLIENT)->exists());

            $settings = PlatformSetting::current();
            $this->assertSame('pro', $settings->plan_tier);
            $this->assertSame('Sunrise Library', $settings->display_name);
            $this->assertNotNull($settings->subscription_trial_started_at);
        });
    }

    public function test_reserved_taken_and_malformed_subdomains_are_rejected(): void
    {
        $trials = $this->trials();
        $this->registerSunrise($trials);
        $this->hosting->subdomains[] = 'existing';

        foreach (['www', 'demo', 'sunrise', 'existing', 'ab', '9lives', '---'] as $name) {
            try {
                $trials->normalizeAndValidateSubdomain($name);
                $this->fail("Expected {$name} to be rejected.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('subdomain', $e->errors());
            }
        }

        $this->assertSame('green-valley', $trials->normalizeAndValidateSubdomain('Green Valley'));
    }

    public function test_failed_provisioning_removes_the_subdomain_it_created(): void
    {
        $this->hosting->failCreateDatabase = new \RuntimeException('Database limit reached');

        $trials = $this->trials();
        $tenant = $trials->provision($this->registerSunrise($trials));

        $this->assertSame(Tenant::STATUS_FAILED, $tenant->status);
        $this->assertSame('database', $tenant->provision_step);
        $this->assertFalse($tenant->active);
        $this->assertFalse($tenant->hosting_subdomain_created);
        $this->assertSame(['sunrise'], $this->hosting->deletedSubdomains);
        $this->assertSame([], $this->hosting->deletedDatabases);
        $this->assertStringContainsString('Database limit reached', $tenant->provision_error);
    }

    public function test_new_trials_stop_at_the_database_cap(): void
    {
        Config::set('libcontrol.trials.max_databases', 280);
        $this->hosting->existingDatabases = 280;

        $this->expectException(ValidationException::class);

        $this->registerSunrise($this->trials());
    }

    public function test_trial_goes_live_with_a_note_when_ssl_is_still_installing(): void
    {
        $this->hosting->sslActive = false;

        $trials = $this->trials();
        $tenant = $trials->provision($this->registerSunrise($trials));

        $this->assertSame(Tenant::STATUS_READY, $tenant->status);
        $this->assertStringContainsString('SSL', (string) $tenant->provision_error);
    }

    public function test_tenant_connection_uses_its_own_database_credentials(): void
    {
        Config::set('database.connections.mysql_landlord', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => 'landlord',
            'username' => 'landlord_user',
            'password' => 'landlord_pass',
        ]);
        Config::set('libcontrol.tenancy.landlord_connection', 'mysql_landlord');

        $tenant = new Tenant([
            'database_name' => 'u1_lc7abcd',
            'db_username' => 'u1_lc7abcd',
            'db_password' => 'tenant-pass',
        ]);

        $manager = $this->app->make(TenantConnectionManager::class);

        try {
            $manager->useTenant($tenant);
        } catch (\Throwable) {
            // No MySQL server in tests; only the resolved config matters.
        }

        $this->assertSame('u1_lc7abcd', config('database.connections.tenant.database'));
        $this->assertSame('u1_lc7abcd', config('database.connections.tenant.username'));
        $this->assertSame('tenant-pass', config('database.connections.tenant.password'));

        Config::set('database.default', 'sqlite');
    }

    public function test_hostinger_provider_calls_the_hosting_api(): void
    {
        Http::fake([
            '*/databases?*' => Http::response(['data' => [['name' => 'u843330709_a']], 'meta' => ['current_page' => 1, 'per_page' => 1, 'total' => 63]]),
            '*/databases' => Http::response(['message' => 'Request accepted']),
            '*/subdomains' => Http::response(['message' => 'Request accepted']),
            '*/ssl/status' => Http::response(['status' => 'active']),
        ]);

        $provider = new HostingerProvider('token-123', 'u843330709', 'libcontrol.in', 'https://api.test');

        $provider->createSubdomain('sunrise');
        $created = $provider->createDatabase('lc7abcd', 'Pass123456789Aa9');

        $this->assertTrue($provider->sslActive('sunrise'));
        $this->assertSame(63, $provider->databaseCount());
        $this->assertSame(['database' => 'u843330709_lc7abcd', 'username' => 'u843330709_lc7abcd'], $created);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.test/api/hosting/v1/accounts/u843330709/websites/libcontrol.in/subdomains'
            && $r['subdomain'] === 'sunrise'
            && $r['is_using_public_directory'] === true
            && $r->hasHeader('Authorization', 'Bearer token-123'));

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.test/api/hosting/v1/accounts/u843330709/databases'
            && $r['name'] === 'lc7abcd'
            && $r['user'] === 'lc7abcd'
            && $r['website_domain'] === 'libcontrol.in');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.test/api/hosting/v1/accounts/u843330709/websites/sunrise.libcontrol.in/ssl/status');
    }
}
