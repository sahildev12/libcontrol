<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Developer\TenantPortalSettingsService;
use App\Services\Tenancy\TenantConnectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeveloperPortalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.phenomit.com']);
    }

    public function test_developer_can_view_portal_settings_picker(): void
    {
        $tenant = Tenant::query()->create([
            'subdomain' => 'demo-picker',
            'client_name' => 'Demo Hosted Library',
            'database_name' => storage_path('framework/testing/unused-picker.sqlite'),
            'plan_tier' => 'starter',
            'active' => true,
            'provisioned_at' => now(),
        ]);

        $this->actingAs($this->developerAdmin())
            ->get('http://libcontrol.phenomit.com/developer/portals')
            ->assertOk()
            ->assertSee('Portal Settings', false)
            ->assertSee($tenant->client_name, false);
    }

    public function test_developer_can_open_portal_settings_for_provisioned_tenant(): void
    {
        $tenant = $this->createProvisionedTenant();

        $this->actingAs($this->developerAdmin())
            ->get($this->landlordUrl(route('developer.portals.settings', $tenant, false)))
            ->assertOk()
            ->assertSee('Portal settings', false)
            ->assertSee($tenant->client_name, false)
            ->assertSee('Website', false);
    }

    public function test_unprovisioned_tenant_settings_are_blocked(): void
    {
        $tenant = Tenant::query()->create([
            'subdomain' => 'pending',
            'client_name' => 'Pending Library',
            'database_name' => storage_path('framework/testing/unused.sqlite'),
            'plan_tier' => 'starter',
            'active' => true,
        ]);

        $this->actingAs($this->developerAdmin())
            ->get($this->landlordUrl(route('developer.portals.settings', $tenant, false)))
            ->assertForbidden();
    }

    public function test_platform_settings_update_persists_in_tenant_database(): void
    {
        $tenant = $this->createProvisionedTenant();
        $user = $this->developerAdmin();

        $this->actingAs($user)
            ->get($this->landlordUrl(route('developer.portals.settings', $tenant, false)))
            ->assertOk();

        $this->actingAs($user)
            ->patchJson($this->landlordUrl(route('developer.portals.settings.platform.update', $tenant, false)), [
                'student_code_prefix' => 'ABC',
                'student_code_padding' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('platform_settings.student_code_prefix', 'ABC');

        app(TenantConnectionManager::class)->runOnTenant($tenant, function () {
            $settings = PlatformSetting::current();
            $this->assertSame('ABC', $settings->student_code_prefix);
            $this->assertSame(4, (int) $settings->student_code_padding);
        });
    }

    public function test_branch_settings_update_persists_in_tenant_database(): void
    {
        $tenant = $this->createProvisionedTenant();
        $branchId = app(TenantConnectionManager::class)->runOnTenant($tenant, fn () => (int) Branch::query()->value('id'));
        $this->restoreLandlordConnection();
        $user = $this->developerAdmin();

        $this->actingAs($user)
            ->withSession([
                TenantPortalSettingsService::SESSION_TENANT_KEY => $tenant->id,
                TenantPortalSettingsService::SESSION_BRANCH_KEY => $branchId,
            ])
            ->patchJson($this->landlordUrl(route('developer.portals.settings.branch.update', $tenant, false)), [
                'library_open_time' => '08:00',
                'library_close_time' => '20:00',
                'is_open_24_hours' => false,
            ])
            ->assertOk();

        app(TenantConnectionManager::class)->runOnTenant($tenant, function () use ($branchId) {
            $branch = Branch::query()->findOrFail($branchId);
            $this->assertSame('08:00', substr((string) $branch->library_open_time, 0, 5));
            $this->assertSame('20:00', substr((string) $branch->library_close_time, 0, 5));
        });
    }

    public function test_client_admin_cannot_access_portal_settings(): void
    {
        $tenant = $this->createProvisionedTenant();
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get($this->landlordUrl(route('developer.portals.index', absolute: false)))
            ->assertForbidden();
    }

    private function landlordUrl(string $path): string
    {
        return 'http://libcontrol.phenomit.com'.(str_starts_with($path, '/') ? $path : '/'.$path);
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

    private function createProvisionedTenant(): Tenant
    {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $databasePath = $directory.DIRECTORY_SEPARATOR.'tenant_portal_'.uniqid('', true).'.sqlite';
        if (file_exists($databasePath)) {
            unlink($databasePath);
        }

        $tenant = Tenant::query()->create([
            'subdomain' => 'demo-'.uniqid(),
            'client_name' => 'Demo Hosted Library',
            'database_name' => $databasePath,
            'plan_tier' => 'starter',
            'active' => true,
            'provisioned_at' => now(),
        ]);

        app(TenantConnectionManager::class)->runOnTenant($tenant, function () {
            Artisan::call('migrate', ['--force' => true]);
            PlatformSetting::query()->firstOrCreate([], [
                'student_code_prefix' => 'LIB',
                'student_code_padding' => 3,
                'display_name' => 'Demo Hosted Library',
            ]);
            Branch::factory()->create(['name' => 'Main Branch']);
        });

        $this->restoreLandlordConnection();

        return $tenant;
    }

    private function restoreLandlordConnection(): void
    {
        app(TenantConnectionManager::class)->useLandlord();
    }
}
