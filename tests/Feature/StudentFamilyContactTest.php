<?php

namespace Tests\Feature;

use App\Addons\Attendance\Services\AttendanceService;
use App\Models\Branch;
use App\Models\FamilyGroup;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InstallsAttendanceAddon;
use Tests\TestCase;

class StudentFamilyContactTest extends TestCase
{
    use InstallsAttendanceAddon;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);
    }

    public function test_student_can_be_created_without_contact_when_setting_disabled(): void
    {
        $branch = Branch::factory()->create(['require_student_contact' => false]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'No Contact Student',
            'gender' => 'male',
            'date_of_birth' => '2001-01-15',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('students', [
            'name' => 'No Contact Student',
            'phone' => null,
            'email' => null,
        ]);
    }

    public function test_student_requires_contact_when_branch_setting_enabled(): void
    {
        $branch = Branch::factory()->create(['require_student_contact' => true]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Missing Contact',
            'gender' => 'female',
            'date_of_birth' => '2001-01-15',
        ])->assertStatus(422)->assertJsonValidationErrors(['phone']);

        $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Phone Only',
            'gender' => 'female',
            'date_of_birth' => '2001-01-15',
            'phone' => '9876543210',
        ])->assertCreated();
    }

    public function test_linked_sibling_can_share_family_contact(): void
    {
        $branch = Branch::factory()->create(['require_student_contact' => true]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $elder = Student::factory()->create([
            'branch_id' => $branch->id,
            'student_code' => 'LIB-001',
            'name' => 'Elder Sibling',
            'phone' => '9876543210',
            'email' => 'family@example.com',
        ]);

        $response = $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Younger Sibling',
            'gender' => 'female',
            'date_of_birth' => '2003-01-15',
            'link_to_student_id' => $elder->id,
        ]);

        $response->assertCreated();

        $younger = Student::query()->where('name', 'Younger Sibling')->first();
        $this->assertNotNull($younger);
        $this->assertSame($elder->fresh()->family_group_id, $younger->family_group_id);
        $this->assertSame('9876543210', $younger->effectivePhone());
        $this->assertNull($younger->phone);
    }

    public function test_two_students_can_share_same_phone_when_not_unique(): void
    {
        $branch = Branch::factory()->create(['require_student_contact' => false]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Student A',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'phone' => '9876543210',
        ])->assertCreated();

        $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Student B',
            'gender' => 'female',
            'date_of_birth' => '2001-01-15',
            'phone' => '9876543210',
        ])->assertCreated();

        $this->assertSame(2, Student::query()->where('phone', '9876543210')->count());
    }

    public function test_primary_family_contact_update_syncs_to_family_group(): void
    {
        $branch = Branch::factory()->create(['require_student_contact' => true]);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $group = FamilyGroup::query()->create([
            'branch_id' => $branch->id,
            'guardian_name' => 'Parent Name',
            'phone' => '9876543210',
            'email' => 'family@example.com',
        ]);

        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'family_group_id' => $group->id,
            'is_family_primary' => true,
            'phone' => null,
            'email' => null,
        ]);

        $this->actingAs($user)->patchJson(route('students.update', $student), [
            'name' => $student->name,
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'student_type' => 'regular',
            'phone' => '9123456789',
            'email' => 'updated@example.com',
            'father_name' => 'Updated Parent',
            'status' => 'active',
        ])->assertOk();

        $group->refresh();
        $this->assertSame('9123456789', $group->phone);
        $this->assertSame('updated@example.com', $group->email);
        $this->assertSame('Updated Parent', $group->guardian_name);
    }

    public function test_attendance_qr_accepts_family_shared_phone(): void
    {
        $this->installAttendanceAddon();

        $branch = Branch::factory()->create();
        $group = FamilyGroup::query()->create([
            'branch_id' => $branch->id,
            'phone' => '9876543210',
        ]);

        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'student_code' => 'LIB-010',
            'family_group_id' => $group->id,
            'phone' => null,
        ]);

        $settings = app(AttendanceService::class)->settingsForBranch($branch->id);

        $this->get('/attendance/check-in/'.$settings->qr_token)->assertOk();

        $this->post('/attendance/check-in/'.$settings->qr_token, [
            'student_code' => $student->student_code,
            'phone' => '9876543210',
        ])->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_branch_can_toggle_require_student_contact_setting(): void
    {
        $branch = Branch::factory()->create(['require_student_contact' => false]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->patchJson(route('settings.update'), [
            'require_student_contact' => true,
        ])->assertOk()->assertJsonPath('settings.require_student_contact', true);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'require_student_contact' => 1,
        ]);
    }
}
