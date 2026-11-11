<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTipTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_complete_admin_tip_tour(): void
    {
        $this->postJson(route('admin-tour.complete'))
            ->assertUnauthorized();
    }

    public function test_client_admin_can_complete_admin_tip_tour(): void
    {
        $admin = $this->clientAdmin();

        $this->assertNull($admin->adminProfile?->tour_completed_at);

        $this->actingAs($admin)
            ->postJson(route('admin-tour.complete'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('tour_completed', true);

        $this->assertNotNull($admin->adminProfile?->fresh()->tour_completed_at);
    }

    public function test_completing_tour_twice_is_idempotent(): void
    {
        $admin = $this->clientAdmin();

        $this->actingAs($admin)->postJson(route('admin-tour.complete'))->assertOk();
        $firstCompletedAt = $admin->adminProfile?->fresh()->tour_completed_at;
        $this->assertNotNull($firstCompletedAt);

        $this->actingAs($admin)->postJson(route('admin-tour.complete'))->assertOk();

        $this->assertTrue(
            $firstCompletedAt->equalTo($admin->adminProfile?->fresh()->tour_completed_at)
        );
    }

    public function test_developer_admin_cannot_complete_admin_tip_tour(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        // Developers are redirected away from library-ops routes before the controller runs.
        $this->actingAs($user)
            ->post(route('admin-tour.complete'))
            ->assertRedirect();

        $this->assertNull($user->adminProfile?->fresh()->tour_completed_at);
    }

    private function clientAdmin(): User
    {
        Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        return $user->fresh();
    }
}
