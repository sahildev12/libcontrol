<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMinimumAgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);
    }

    public function test_store_rejects_students_younger_than_five(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $tooYoung = now()->subYears(4)->toDateString();

        $this->actingAs($user)
            ->postJson(route('students.store'), [
                'name' => 'Young Kid',
                'gender' => 'male',
                'date_of_birth' => $tooYoung,
                'phone' => '9876543210',
                'email' => 'kid@example.com',
                'student_type' => 'regular',
                'branch_id' => $branch->id,
                'status' => 'active',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);

        $this->assertDatabaseMissing('students', ['name' => 'Young Kid']);
    }

    public function test_store_accepts_students_at_least_five_years_old(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $oldEnough = now()->subYears(5)->toDateString();

        $this->actingAs($user)
            ->postJson(route('students.store'), [
                'name' => 'Ready Kid',
                'gender' => 'female',
                'date_of_birth' => $oldEnough,
                'phone' => '9876543211',
                'email' => 'ready@example.com',
                'student_type' => 'regular',
                'branch_id' => $branch->id,
                'status' => 'active',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('students', ['name' => 'Ready Kid']);
    }
}
