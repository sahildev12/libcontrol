<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_can_view_settings_page(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->get(route('settings.index'))->assertOk()->assertSee('Settings')->assertSee('Library hours');
    }

    public function test_branch_user_can_update_branch_settings(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->patchJson(route('settings.update'), [
            'display_name' => 'City Library',
            'expiry_reminder_days' => 12,
        ]);

        $response->assertOk()->assertJsonPath('settings.display_name', 'City Library');
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'display_name' => 'City Library',
            'expiry_reminder_days' => 12,
        ]);
    }

    public function test_platform_admin_can_update_global_student_code_settings(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $response = $this->actingAs($user)->patchJson(route('settings.platform.update'), [
            'student_code_prefix' => 'CIT',
            'student_code_padding' => 4,
        ]);

        $response->assertOk()->assertJsonPath('platform_settings.student_code_prefix', 'CIT');
        $this->assertDatabaseHas('platform_settings', [
            'student_code_prefix' => 'CIT',
            'student_code_padding' => 4,
        ]);
    }

    public function test_student_code_uses_global_prefix(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'CIT',
            'student_code_padding' => 3,
        ]);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('students.store'), [
            'name' => 'Test Student',
            'gender' => 'female',
            'date_of_birth' => '1999-05-20',
            'phone' => '9876543210',
            'email' => 'test@example.com',
        ]);

        $response->assertCreated()->assertJsonPath('student.student_code', 'CIT-001');
    }

    public function test_branch_create_rejects_invalid_phone(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $response = $this->actingAs($user)->postJson(route('branch.store'), [
            'name' => 'East Branch',
            'phone' => 'asdfasdfas',
            'email' => 'east@libspace.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_developer_admin_can_clear_application_cache(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.clear-cache'))
            ->assertOk()
            ->assertJsonPath('message', 'Application cache cleared.');
    }

    public function test_non_developer_admin_cannot_clear_application_cache(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.clear-cache'))
            ->assertForbidden();
    }
}
