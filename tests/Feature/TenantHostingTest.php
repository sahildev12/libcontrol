<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantHostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.base_domain', 'phenomit.com');
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.phenomit.com']);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
    }

    public function test_landlord_host_keeps_tenant_registry_on_default_connection(): void
    {
        Tenant::query()->create([
            'subdomain' => 'dise',
            'client_name' => 'DISE Library',
            'database_name' => 'tenant_dise',
            'plan_tier' => 'starter',
            'active' => true,
        ]);

        $this->get('http://libcontrol.phenomit.com/up')->assertOk();
        $this->assertDatabaseHas('tenants', ['subdomain' => 'dise']);
    }
}
