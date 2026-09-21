<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Developer\TenantRemoteManageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantRemoteManageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
        Config::set('libcontrol.tenancy.enabled', false);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
    }

    public function test_manage_payload_includes_tenant_plan_snapshot(): void
    {
        $tenant = Tenant::query()->create([
            'subdomain' => 'dise',
            'client_name' => 'DISE Library',
            'database_name' => 'tenant_dise',
            'plan_tier' => 'starter',
            'active' => true,
        ]);

        $payload = app(TenantRemoteManageService::class)->managePayload($tenant);

        $this->assertSame('DISE Library', $payload['tenant']->client_name);
        $this->assertSame('starter', $payload['planSnapshot']['plan_tier']);
    }

    public function test_update_plan_persists_on_tenant_record(): void
    {
        $tenant = Tenant::query()->create([
            'subdomain' => 'dise',
            'client_name' => 'DISE Library',
            'database_name' => 'tenant_dise',
            'plan_tier' => 'starter',
            'active' => true,
        ]);

        $user = $this->developerAdmin();

        app(TenantRemoteManageService::class)->updatePlan($tenant, [
            'plan_tier' => 'pro',
            'max_seats_override' => 120,
        ], $user, null);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'plan_tier' => 'pro',
            'max_seats_override' => 120,
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
