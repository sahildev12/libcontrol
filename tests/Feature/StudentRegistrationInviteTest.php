<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\StudentRegistrationInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentRegistrationInviteTest extends TestCase
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

    public function test_authenticated_user_can_create_registration_invite(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('students.registration-invites.store'));

        $response->assertCreated()
            ->assertJsonStructure(['invite' => ['url', 'expires_at', 'expires_label']]);

        $this->assertDatabaseCount('student_registration_invites', 1);
    }

    public function test_public_registration_creates_student_and_expires_invite(): void
    {
        $branch = Branch::factory()->create();
        $invite = StudentRegistrationInvite::createForBranch($branch->id);

        $response = $this->post(route('students.register.store', $invite->token), [
            'name' => 'Rahul Sharma',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'phone' => '9876543210',
            'email' => 'rahul@example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('students', [
            'branch_id' => $branch->id,
            'name' => 'Rahul Sharma',
            'phone' => '9876543210',
        ]);

        $invite->refresh();
        $this->assertNotNull($invite->used_at);
        $this->assertNotNull($invite->student_id);
    }

    public function test_used_registration_link_cannot_be_reused(): void
    {
        $branch = Branch::factory()->create();
        $invite = StudentRegistrationInvite::createForBranch($branch->id);

        $payload = [
            'name' => 'First Student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'phone' => '9876543210',
            'email' => 'first@example.com',
        ];

        $this->post(route('students.register.store', $invite->token), $payload)->assertOk();

        $this->post(route('students.register.store', $invite->token), [
            'name' => 'Second Student',
            'gender' => 'female',
            'date_of_birth' => '2001-02-20',
            'phone' => '9123456780',
            'email' => 'second@example.com',
        ])->assertStatus(410);

        $this->assertSame(1, Student::query()->count());
    }

    public function test_expired_registration_link_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        $invite = StudentRegistrationInvite::createForBranch($branch->id);
        $invite->update(['expires_at' => Carbon::now()->subMinute()]);

        $this->get(route('students.register.show', $invite->token))
            ->assertOk()
            ->assertSee('Link Expired');

        $this->post(route('students.register.store', $invite->token), [
            'name' => 'Late Student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'phone' => '9876543210',
            'email' => 'late@example.com',
        ])->assertStatus(410);
    }
}
