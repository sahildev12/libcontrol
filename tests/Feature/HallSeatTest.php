<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HallSeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_can_view_seat_map(): void
    {
        $branch = Branch::factory()->create(['name' => 'Main Center']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->seed(\Database\Seeders\HallSeeder::class);

        $response = $this->actingAs($user)->get(route('seats.index'));

        $response->assertOk();
        $response->assertSee('Vacant');
        $response->assertSee('Occupied');
    }

    public function test_branch_user_can_create_hall_via_json(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('halls.store'), [
            'name' => 'Reading Hall',
            'description' => 'Quiet zone',
            'seat_capacity' => 12,
        ]);

        $response->assertCreated()->assertJsonPath('hall.name', 'Reading Hall');
        $this->assertDatabaseHas('halls', [
            'branch_id' => $branch->id,
            'name' => 'Reading Hall',
            'seat_capacity' => 12,
        ]);
        $this->assertDatabaseCount('seats', 12);
        $this->assertDatabaseHas('seats', ['seat_number' => '1']);
        $this->assertDatabaseHas('seats', ['seat_number' => '12']);
    }

    public function test_new_hall_starts_at_seat_one_by_default_when_branch_has_other_halls(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $firstHall = Hall::factory()->create([
            'branch_id' => $branch->id,
            'seat_capacity' => 10,
        ]);

        app(\App\Services\HallSeatGenerator::class)->generate($firstHall);

        $response = $this->actingAs($user)->postJson(route('halls.store'), [
            'name' => 'Second Hall',
            'seat_capacity' => 8,
        ]);

        $response->assertCreated();

        $secondHallId = (int) $response->json('hall.id');

        $this->assertDatabaseHas('seats', [
            'hall_id' => $secondHallId,
            'seat_number' => '1',
        ]);
        $this->assertDatabaseHas('seats', [
            'hall_id' => $secondHallId,
            'seat_number' => '8',
        ]);
        $this->assertDatabaseMissing('seats', [
            'hall_id' => $secondHallId,
            'seat_number' => '11',
        ]);
    }

    public function test_new_hall_continues_seat_numbering_when_user_selects_source_hall(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $firstHall = Hall::factory()->create([
            'branch_id' => $branch->id,
            'seat_capacity' => 10,
        ]);

        app(\App\Services\HallSeatGenerator::class)->generate($firstHall);

        $response = $this->actingAs($user)->postJson(route('halls.store'), [
            'name' => 'Second Hall',
            'seat_capacity' => 8,
            'continue_seat_numbering' => true,
            'continue_from_hall_id' => $firstHall->id,
        ]);

        $response->assertCreated();

        $secondHallId = (int) $response->json('hall.id');

        $this->assertDatabaseHas('seats', [
            'hall_id' => $secondHallId,
            'seat_number' => '11',
        ]);
        $this->assertDatabaseHas('seats', [
            'hall_id' => $secondHallId,
            'seat_number' => '18',
        ]);
        $this->assertDatabaseMissing('seats', [
            'hall_id' => $secondHallId,
            'seat_number' => '1',
        ]);
    }

    public function test_continuing_seat_numbering_requires_source_hall(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('halls.store'), [
            'name' => 'Second Hall',
            'seat_capacity' => 8,
            'continue_seat_numbering' => true,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['continue_from_hall_id']);
    }

    public function test_branch_user_can_bulk_delete_halls(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hallA = Hall::factory()->create(['branch_id' => $branch->id]);
        $hallB = Hall::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('halls.bulk-destroy'), [
            'ids' => [$hallA->id, $hallB->id],
        ]);

        $response->assertOk()->assertJson(['deleted' => 2]);
        $this->assertDatabaseMissing('halls', ['id' => $hallA->id]);
        $this->assertDatabaseMissing('halls', ['id' => $hallB->id]);
    }

    public function test_halls_export_downloads_csv(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        Hall::factory()->create(['branch_id' => $branch->id, 'name' => 'Export Hall']);

        $response = $this->actingAs($user)->get(route('halls.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_webhook_triggers_seat_map_refresh(): void
    {
        config(['services.libspace.webhook_token' => 'test-token']);

        $branch = Branch::factory()->create();

        $response = $this->postJson(route('webhooks.seat-map'), [
            'branch_id' => $branch->id,
        ], [
            'X-LibSpace-Webhook-Token' => 'test-token',
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'branch_id' => $branch->id]);
    }

    public function test_platform_admin_can_create_hall_for_any_branch(): void
    {
        $adminUser = User::factory()->create(['branch_id' => null]);
        \App\Models\Admin::query()->create([
            'user_id' => $adminUser->id,
            'admin_type' => \App\Models\Admin::TYPE_DEVELOPER,
        ]);
        $active = Branch::factory()->create(['name' => 'Active Branch']);
        $other = Branch::factory()->create(['name' => 'Other Branch']);

        $this->actingAs($adminUser)->withSession(['active_branch_id' => $active->id]);

        $response = $this->postJson(route('halls.store'), [
            'branch_id' => $other->id,
            'name' => 'East Reading Hall',
            'seat_capacity' => 8,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('halls', [
            'branch_id' => $other->id,
            'name' => 'East Reading Hall',
            'seat_capacity' => 8,
        ]);
    }
}
