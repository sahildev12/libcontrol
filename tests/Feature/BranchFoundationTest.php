<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Hall;
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

    public function test_platform_admin_cannot_delete_branch_with_halls_or_students(): void
    {
        Branch::factory()->create();
        $branch = Branch::factory()->create(['name' => 'Busy Branch']);
        Hall::factory()->create(['branch_id' => $branch->id]);
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->deleteJson(route('branch.destroy', $branch))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete a branch that has halls or students. Remove or reassign them first.');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_platform_admin_can_delete_empty_branch(): void
    {
        Branch::factory()->create();
        $branch = Branch::factory()->create(['name' => 'Empty Branch']);
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->deleteJson(route('branch.destroy', $branch))
            ->assertOk()
            ->assertJsonPath('message', 'Branch "Empty Branch" deleted.');

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
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

    private function platformAdmin(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        return $user;
    }
}
