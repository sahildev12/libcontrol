<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\InstallationEvent;
use App\Models\LicensedDeployment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeveloperDeploymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
    }

    public function test_developer_admin_can_view_deployments_index(): void
    {
        $user = $this->developerAdmin();

        $this->actingAs($user)
            ->get(route('developer.deployments.index'))
            ->assertOk()
            ->assertSee('Dev &amp; Domains', false)
            ->assertSee('Unauthorized domains', false)
            ->assertSee('Authorized clients', false)
            ->assertSee('unauthorizedDomainTable', false);
    }

    public function test_installations_route_redirects_to_deployments_index(): void
    {
        $user = $this->developerAdmin();

        $this->actingAs($user)
            ->get(route('developer.deployments.installations', ['filter' => 'unauthorized']))
            ->assertRedirect(route('developer.deployments.index', ['tab' => 'unauthorized']));
    }

    public function test_client_admin_cannot_view_deployments_index(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->get(route('developer.deployments.index'))
            ->assertForbidden();
    }

    public function test_unauthorized_domain_can_be_prefilled_when_authorizing(): void
    {
        $user = $this->developerAdmin();

        InstallationEvent::query()->create([
            'license_key_hash' => LicensedDeployment::discoveryKeyHash(),
            'domain' => 'aims.phenomit.com',
            'app_url' => 'https://aims.phenomit.com',
            'fingerprint' => hash('sha256', 'install-aims'),
            'is_authorized' => false,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'hit_count' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('developer.deployments.index'))
            ->assertOk()
            ->assertSee('aims.phenomit.com', false)
            ->assertSee('Authorize', false);

        $this->actingAs($user)
            ->get(route('developer.deployments.create', ['domain' => 'aims.phenomit.com']))
            ->assertOk()
            ->assertSee('Aims', false)
            ->assertSee('aims.phenomit.com', false);
    }

    public function test_developer_admin_can_create_deployment(): void
    {
        $user = $this->developerAdmin();

        $response = $this->actingAs($user)->post(route('developer.deployments.store'), [
            'client_name' => 'North Library',
            'allowed_domains' => "north.test\nwww.north.test",
            'grace_days' => 7,
            'active' => 1,
            'notes' => 'Pilot client',
        ]);

        $deployment = LicensedDeployment::query()->first();

        $this->assertNotNull($deployment);
        $response->assertRedirect(route('developer.deployments.edit', $deployment));
        $this->assertDatabaseHas('licensed_deployments', [
            'client_name' => 'North Library',
            'grace_days' => 7,
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
