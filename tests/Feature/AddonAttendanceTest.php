<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Models\BranchAttendanceSetting;
use App\Services\Addons\AddonRegistry;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\AttendanceTestCase;
use Tests\InstallsAttendanceAddon;

class AddonAttendanceTest extends AttendanceTestCase
{
    use DatabaseMigrations;
    use InstallsAttendanceAddon;

    private function platformAdmin(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        return $user;
    }

    public function test_platform_admin_can_install_attendance_addon(): void
    {
        $user = $this->platformAdmin();

        $this->actingAs($user);

        app(AddonRegistry::class)->install('attendance');

        $this->assertTrue(app(AddonRegistry::class)->isEnabled('attendance'));
        $this->assertDatabaseHas('addon_installations', [
            'slug' => 'attendance',
            'enabled' => true,
        ]);
        $this->assertTrue(Schema::hasTable('attendance_records'));
    }

    public function test_attendance_nav_hidden_until_addon_enabled(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->assertFalse(app(AddonRegistry::class)->isEnabled('attendance'));

        $this->installAttendanceAddon();

        $this->assertTrue(app(AddonRegistry::class)->isEnabled('attendance'));

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('Attendance');
    }

    public function test_student_qr_check_in_records_attendance(): void
    {
        $branch = Branch::factory()->create();
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'student_code' => 'LIB-001',
            'phone' => '9876543210',
            'status' => 'active',
        ]);

        $this->installAttendanceAddon();

        $settings = BranchAttendanceSetting::forBranch($branch->id);

        $this->get('/attendance/check-in/'.$settings->qr_token)
            ->assertOk()
            ->assertSee('Attendance Check-in');

        $this->post('/attendance/check-in/'.$settings->qr_token, [
            'student_code' => $student->student_code,
            'phone' => $student->phone,
        ])->assertOk()->assertSee('Check-in Successful');

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'method' => AttendanceRecord::METHOD_STUDENT_QR,
        ]);

        $this->post('/attendance/check-in/'.$settings->qr_token, [
            'student_code' => $student->student_code,
            'phone' => $student->phone,
        ])->assertOk()->assertSee('Already Checked In');
    }

    public function test_staff_api_rejects_check_in_outside_geofence(): void
    {
        $this->installAttendanceAddon();

        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'password' => Hash::make('password'),
        ]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $settings = BranchAttendanceSetting::forBranch($branch->id);
        $settings->update([
            'staff_gps_enabled' => true,
            'geofence_latitude' => 18.5204,
            'geofence_longitude' => 73.8567,
            'geofence_radius_meters' => 100,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)->postJson('/api/v1/attendance/check-in', [
            'student_id' => $student->id,
            'latitude' => 19.0760,
            'longitude' => 72.8777,
        ])->assertStatus(422)->assertJsonValidationErrors(['location']);

        $this->withToken($token)->postJson('/api/v1/attendance/check-in', [
            'student_id' => $student->id,
            'latitude' => 18.5204,
            'longitude' => 73.8567,
        ])->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'method' => AttendanceRecord::METHOD_STAFF_GPS,
        ]);
    }
}
