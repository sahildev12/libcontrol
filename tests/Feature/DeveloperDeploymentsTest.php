<?php

namespace Tests\Feature;

use App\Models\Admin;
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

        Config::set('libspace.license_server.enabled', true);
    }

    public function test_developer_admin_can_view_deployments_index(): void
    {
        $user = $this->developerAdmin();

        $this->actingAs($user)
            ->get(route('developer.deployments.index'))
            ->assertOk()
            ->assertSee('Licensed Deployments');
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
