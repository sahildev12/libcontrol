<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAuthTest extends TestCase
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

    public function test_student_can_login_with_code_and_pin(): void
    {
        $branch = Branch::factory()->create(['name' => 'Main Library']);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'student_code' => 'LIB-100',
            'status' => 'active',
        ]);
        $student->setAppPin('123456');

        $response = $this->postJson('/api/v1/student/auth/login', [
            'student_code' => 'LIB-100',
            'pin' => '123456',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonPath('student.student_code', 'LIB-100')
            ->assertJsonPath('student.name', $student->name)
            ->assertJsonPath('student.home_branch', 'Main Library')
            ->assertJsonPath('student.needs_pin_setup', false)
            ->assertJsonStructure(['token', 'student']);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_check_code_returns_pin_setup_for_new_student(): void
    {
        $branch = Branch::factory()->create(['name' => 'Main Library']);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'student_code' => 'LIB-105',
            'status' => 'active',
            'app_pin_hash' => null,
        ]);

        $response = $this->postJson('/api/v1/student/auth/check-code', [
            'student_code' => 'LIB-105',
        ]);

        $response->assertOk()
            ->assertJsonPath('needs_pin_setup', true)
            ->assertJsonPath('student.student_code', 'LIB-105')
            ->assertJsonPath('student.name', $student->name)
            ->assertJsonStructure(['setup_token']);

        $this->assertSame(64, strlen((string) $response->json('setup_token')));
    }

    public function test_check_code_returns_login_path_when_pin_exists(): void
    {
        $student = Student::factory()->create([
            'student_code' => 'LIB-106',
            'status' => 'active',
        ]);
        $student->setAppPin('123456');

        $this->postJson('/api/v1/student/auth/check-code', [
            'student_code' => 'LIB-106',
        ])->assertOk()
            ->assertJsonPath('needs_pin_setup', false)
            ->assertJsonMissing(['setup_token']);
    }

    public function test_setup_pin_creates_pin_and_returns_token(): void
    {
        $student = Student::factory()->create([
            'student_code' => 'LIB-107',
            'status' => 'active',
            'app_pin_hash' => null,
        ]);

        $check = $this->postJson('/api/v1/student/auth/check-code', [
            'student_code' => 'LIB-107',
        ])->assertOk();

        $response = $this->postJson('/api/v1/student/auth/setup-pin', [
            'setup_token' => $check->json('setup_token'),
            'pin' => '567890',
            'pin_confirmation' => '567890',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonPath('student.student_code', 'LIB-107')
            ->assertJsonPath('student.needs_pin_setup', false)
            ->assertJsonStructure(['token']);

        $this->assertTrue($student->fresh()->verifyAppPin('567890'));
    }

    public function test_setup_pin_rejects_reused_token(): void
    {
        $student = Student::factory()->create([
            'student_code' => 'LIB-108',
            'status' => 'active',
            'app_pin_hash' => null,
        ]);

        $check = $this->postJson('/api/v1/student/auth/check-code', [
            'student_code' => 'LIB-108',
        ])->assertOk();

        $token = $check->json('setup_token');

        $this->postJson('/api/v1/student/auth/setup-pin', [
            'setup_token' => $token,
            'pin' => '567890',
            'pin_confirmation' => '567890',
        ])->assertOk();

        $this->postJson('/api/v1/student/auth/setup-pin', [
            'setup_token' => $token,
            'pin' => '111111',
            'pin_confirmation' => '111111',
        ])->assertStatus(422)->assertJsonValidationErrors(['setup_token']);
    }

    public function test_login_fails_with_wrong_pin(): void
    {
        $student = Student::factory()->create([
            'student_code' => 'LIB-101',
            'status' => 'active',
        ]);
        $student->setAppPin('123456');

        $this->postJson('/api/v1/student/auth/login', [
            'student_code' => 'LIB-101',
            'pin' => '999999',
        ])->assertStatus(422)->assertJsonValidationErrors(['student_code']);
    }

    public function test_login_fails_when_pin_not_configured(): void
    {
        Student::factory()->create([
            'student_code' => 'LIB-102',
            'status' => 'active',
            'app_pin_hash' => null,
        ]);

        $this->postJson('/api/v1/student/auth/login', [
            'student_code' => 'LIB-102',
            'pin' => '123456',
        ])->assertStatus(422)->assertJsonValidationErrors(['student_code']);
    }

    public function test_login_fails_for_inactive_student(): void
    {
        $student = Student::factory()->create([
            'student_code' => 'LIB-103',
            'status' => 'inactive',
        ]);
        $student->setAppPin('123456');

        $this->postJson('/api/v1/student/auth/login', [
            'student_code' => 'LIB-103',
            'pin' => '123456',
        ])->assertStatus(422)->assertJsonValidationErrors(['student_code']);
    }

    public function test_me_endpoint_returns_student_profile(): void
    {
        $student = Student::factory()->create([
            'student_code' => 'LIB-104',
            'status' => 'active',
        ]);
        $student->setAppPin('123456');

        Sanctum::actingAs($student, ['*']);

        $this->getJson('/api/v1/student/auth/me')
            ->assertOk()
            ->assertJsonPath('student.student_code', 'LIB-104');
    }

    public function test_admin_create_student_does_not_set_app_pin(): void
    {
        $branch = Branch::factory()->create(['require_student_contact' => false]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'App Student',
            'gender' => 'male',
            'date_of_birth' => '2001-01-15',
        ]);

        $response->assertCreated()
            ->assertJsonMissing(['generated_app_pin']);

        $student = Student::query()->where('name', 'App Student')->first();
        $this->assertNotNull($student);
        $this->assertFalse($student->hasAppPin());
    }

    public function test_admin_can_reset_student_app_login(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create(['branch_id' => $branch->id]);
        $student->setAppPin('111111');

        $response = $this->actingAs($user)->patchJson(route('students.update', $student), [
            'name' => $student->name,
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'student_type' => 'regular',
            'status' => 'active',
            'reset_app_login' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('student.has_app_pin', false);

        $this->assertFalse($student->fresh()->hasAppPin());
    }
}
