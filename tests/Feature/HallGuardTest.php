<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HallGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_hall_capacity_cannot_be_reduced_below_booked_seats(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id, 'seat_capacity' => 20]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => '1']);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1000,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        // One booked seat — cannot go below 1.
        $response = $this->actingAs($user)->patchJson(route('halls.update', $hall), [
            'name' => $hall->name,
            'seat_capacity' => 0,
            'description' => $hall->description,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['seat_capacity']);
    }

    public function test_hall_capacity_can_be_reduced_down_to_booked_seat_count(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id, 'seat_capacity' => 10]);

        // Ensure seats 1-10 exist.
        for ($n = 1; $n <= 10; $n++) {
            Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => (string) $n]);
        }

        $bookedA = Seat::query()->where('hall_id', $hall->id)->where('seat_number', '1')->first();
        $bookedB = Seat::query()->where('hall_id', $hall->id)->where('seat_number', '2')->first();
        $studentA = Student::factory()->create(['branch_id' => $branch->id]);
        $studentB = Student::factory()->create(['branch_id' => $branch->id]);

        foreach ([$bookedA, $bookedB] as $index => $seat) {
            SeatBooking::query()->create([
                'seat_id' => $seat->id,
                'student_id' => $index === 0 ? $studentA->id : $studentB->id,
                'time_slot' => 'full_day',
                'fee_type' => 'monthly',
                'fee_amount' => 1000,
                'joining_date' => now()->toDateString(),
                'plan_expiry_date' => now()->addMonth()->toDateString(),
                'status' => 'occupied',
            ]);
        }

        $response = $this->actingAs($user)->patchJson(route('halls.update', $hall), [
            'name' => $hall->name,
            'seat_capacity' => 5,
            'description' => $hall->description,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('halls', [
            'id' => $hall->id,
            'seat_capacity' => 5,
        ]);
        $this->assertSame(5, Seat::query()->where('hall_id', $hall->id)->count());
        $this->assertDatabaseHas('seats', ['id' => $bookedA->id]);
        $this->assertDatabaseHas('seats', ['id' => $bookedB->id]);
    }

    public function test_hall_capacity_can_be_increased_when_students_are_assigned(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id, 'seat_capacity' => 20]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1000,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->patchJson(route('halls.update', $hall), [
            'name' => $hall->name,
            'seat_capacity' => 25,
            'description' => $hall->description,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('halls', [
            'id' => $hall->id,
            'seat_capacity' => 25,
        ]);
    }

    public function test_hall_cannot_be_deleted_when_students_are_assigned(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);

        SeatBooking::query()->create([
            'seat_id' => $seat->id,
            'student_id' => $student->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1000,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('halls.destroy', $hall));

        $response->assertStatus(422);
        $this->assertDatabaseHas('halls', ['id' => $hall->id]);
    }
}
