<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_authenticated_branch_user_can_access_dashboard(): void
    {
        $branch = Branch::factory()->create(['name' => 'Test Branch Center']);
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'branch-admin@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Test Branch Center');
        $response->assertSee('Dashboard');
    }

    public function test_branch_user_cannot_access_branch_management_page(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->get(route('branch.index'))->assertForbidden();
    }

    public function test_platform_admin_can_access_branch_management_page(): void
    {
        Branch::factory()->create(['name' => 'Main Library Center']);
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $response = $this->actingAs($user)->get(route('branch.index'));

        $response->assertOk();
        $response->assertSee('Branches');
        $response->assertSee('Main Library Center');
    }

    public function test_branch_seeder_creates_sample_branch_users(): void
    {
        $this->seed(\Database\Seeders\BranchSeeder::class);

        $this->assertDatabaseCount('branches', 2);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@main.LibControl.test',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@north.LibControl.test',
        ]);
    }
}
