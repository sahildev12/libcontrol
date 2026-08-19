<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_can_update_branch_details(): void
    {
        $branch = Branch::factory()->create(['name' => 'Main Center']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->patchJson(route('branch.update'), [
            'name' => 'Updated Center',
            'contact_person' => 'Jane Doe',
            'phone' => '9999999999',
            'email' => 'center@example.com',
            'address' => '123 Library Street',
        ]);

        $response->assertOk()->assertJsonPath('branch.name', 'Updated Center');
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Center',
            'contact_person' => 'Jane Doe',
        ]);
    }

    public function test_branch_user_can_create_student(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $branch = Branch::factory()->create(['name' => 'Main']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Rahul Sharma',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'phone' => '9876543210',
            'email' => 'rahul@example.com',
        ]);

        $response->assertCreated()->assertJsonPath('student.name', 'Rahul Sharma');
        $this->assertDatabaseHas('students', [
            'branch_id' => $branch->id,
            'name' => 'Rahul Sharma',
        ]);
    }

    public function test_branch_user_can_create_enquiry_and_convert(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $create = $this->actingAs($user)->postJson(route('enquiries.store'), [
            'name' => 'Lead Person',
            'phone' => '8888888888',
            'message' => 'Interested in monthly plan',
        ]);

        $create->assertCreated();
        $enquiryId = $create->json('enquiry.id');

        $convert = $this->actingAs($user)->postJson(route('enquiries.convert', $enquiryId));
        $convert->assertOk()->assertJsonPath('enquiry.status', 'converted');

        $this->assertDatabaseHas('students', ['name' => 'Lead Person']);
        $this->assertDatabaseHas('enquiries', ['id' => $enquiryId, 'status' => 'converted']);
    }

    public function test_seat_assignment_rejects_conflicting_slot(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id, 'seat_number' => 1]);
        $studentA = Student::factory()->create(['branch_id' => $branch->id]);
        $studentB = Student::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('seat-assignments.store'), [
            'student_id' => $studentA->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1500,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ])->assertCreated();

        $conflict = $this->actingAs($user)->postJson(route('seat-assignments.store'), [
            'student_id' => $studentB->id,
            'hall_id' => $hall->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'fee_type' => 'monthly',
            'fee_amount' => 1200,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $conflict->assertStatus(422);
    }

    public function test_module_pages_load_for_branch_user(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $routes = [
            'students.index',
            'enquiries.index',
            'seat-assignments.index',
            'fees.index',
            'notifications.index',
            'settings.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }
}
