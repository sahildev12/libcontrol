<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityLogAndBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_seats_page_only_lists_unassigned_trial_students(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $regular = Student::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'Existing Regular',
            'student_type' => 'regular',
            'status' => 'active',
        ]);
        $trial = Student::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'Free Trial Student',
            'student_type' => 'trial',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('trial-seats.index'))
            ->assertOk()
            ->assertDontSee('Existing Regular')
            ->assertDontSee($regular->student_code)
            ->assertSee('Free Trial Student')
            ->assertSee($trial->student_code);
    }

    public function test_platform_admin_all_branches_shows_students_from_every_branch(): void
    {
        $admin = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $admin->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $branchA = Branch::factory()->create(['name' => 'Alpha Center']);
        $branchB = Branch::factory()->create(['name' => 'Beta Center']);
        Student::factory()->create(['branch_id' => $branchA->id, 'name' => 'Alpha Student']);
        Student::factory()->create(['branch_id' => $branchB->id, 'name' => 'Beta Student']);

        $this->actingAs($admin)
            ->withSession(['active_branch_id' => 'all'])
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Alpha Student')
            ->assertSee('Beta Student');
    }

    public function test_login_writes_an_activity_log(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_opening_a_page_writes_a_view_log(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'page.viewed',
        ]);
    }

    public function test_activity_log_show_returns_details(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->get(route('students.index'))->assertOk();

        $log = \App\Models\ActivityLog::query()->where('user_id', $user->id)->latest('id')->first();

        $this->actingAs($user)
            ->getJson(route('activity-logs.show', $log))
            ->assertOk()
            ->assertJsonPath('id', $log->id)
            ->assertJsonStructure(['created_at_full', 'description', 'user_name', 'details']);
    }

    public function test_updating_a_student_records_old_and_new_values(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'Old Name',
            'phone' => '9876543210',
            'email' => 'old@example.com',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'student_type' => 'regular',
            'status' => 'active',
        ]);

        $this->actingAs($user)->patchJson(route('students.update', $student), [
            'name' => 'New Name',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'phone' => '9123456789',
            'email' => 'new@example.com',
            'status' => 'active',
            'student_type' => 'regular',
        ])->assertOk();

        $log = \App\Models\ActivityLog::query()
            ->where('action', 'student.updated')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('New Name', (string) $log->description);
        $changes = collect($log->properties['changes'] ?? []);
        $this->assertTrue($changes->contains(fn ($change) => ($change['label'] ?? '') === 'Name' && ($change['from'] ?? '') === 'Old Name' && ($change['to'] ?? '') === 'New Name'));
        $this->assertTrue($changes->contains(fn ($change) => ($change['label'] ?? '') === 'Phone' && str_contains((string) ($change['from'] ?? ''), '9876543210')));

        $this->actingAs($user)
            ->getJson(route('activity-logs.show', $log))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Name', 'from' => 'Old Name', 'to' => 'New Name']);
    }
}
