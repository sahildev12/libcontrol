<?php

namespace Tests\Feature;

use App\Mail\StudentNotificationMail;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentOffersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
            'email_offers_enabled' => true,
        ]);

        $this->configureMailForDelivery();
    }

    private function configureMailForDelivery(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => 'library@example.com',
            'mail.mailers.smtp.password' => 'secret',
            'mail.from.address' => 'library@example.com',
        ]);
    }

    public function test_offers_page_is_accessible(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('offers.index'))
            ->assertOk()
            ->assertSee('Offers', false)
            ->assertSee('Compose offer email', false);
    }

    public function test_user_can_send_offer_email_to_selected_students(): void
    {
        Mail::fake();

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $first = Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'first@example.com',
            'status' => 'active',
        ]);

        $second = Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'second@example.com',
            'status' => 'active',
        ]);

        Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('offers.send'), [
            'subject' => 'September discount',
            'message' => 'Get 20% off your next renewal.',
            'action_url' => 'https://example.com/offer',
            'audience' => 'selected',
            'student_ids' => [$first->id, $second->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('result.sent', 2);

        Mail::assertSent(StudentNotificationMail::class, 2);
    }

    public function test_user_can_send_offer_email_to_all_students_with_email(): void
    {
        Mail::fake();

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'first@example.com',
            'status' => 'active',
        ]);

        Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'second@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($user)->postJson(route('offers.send'), [
            'subject' => 'Library-wide offer',
            'message' => 'Special discount for everyone.',
            'audience' => 'all',
            'student_ids' => [],
        ])->assertOk()
            ->assertJsonPath('result.sent', 2);

        Mail::assertSent(StudentNotificationMail::class, 2);
    }

    public function test_offer_send_fails_when_mail_delivery_is_not_configured(): void
    {
        config(['mail.default' => 'log']);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'student@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($user)->postJson(route('offers.send'), [
            'subject' => 'Blocked offer',
            'message' => 'This should not send.',
            'audience' => 'selected',
            'student_ids' => [$student->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['mail']);
    }

    public function test_offer_send_is_blocked_when_offers_disabled(): void
    {
        PlatformSetting::current()->update(['email_offers_enabled' => false]);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'student@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($user)->postJson(route('offers.send'), [
            'subject' => 'Blocked offer',
            'message' => 'This should not send.',
            'audience' => 'selected',
            'student_ids' => [$student->id],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['offers']);
    }
}
