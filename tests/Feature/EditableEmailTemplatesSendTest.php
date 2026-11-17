<?php

namespace Tests\Feature;

use App\Mail\AutomationAlertMail;
use App\Mail\StudentNotificationMail;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\FeeInstallment;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\EmailAutomation\FeeAlertService;
use App\Services\StudentEmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EditableEmailTemplatesSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_email_uses_custom_template(): void
    {
        Mail::fake();
        PlatformSetting::current()->update([
            'display_name' => 'Sunrise Library',
            'email_welcome_enabled' => true,
            'email_template_overrides' => [
                'welcome' => [
                    'subject' => 'Namaste {name}',
                    'body' => "Hello {name} from {library}. Code {student_code}",
                ],
            ],
        ]);

        $student = Student::factory()->create([
            'name' => 'Asha',
            'email' => 'asha@example.com',
            'student_code' => 'SUN001',
        ]);

        $this->assertTrue(app(StudentEmailNotificationService::class)->sendWelcome($student));

        Mail::assertSent(StudentNotificationMail::class, function (StudentNotificationMail $mail) {
            return $mail->subjectLine === 'Namaste Asha'
                && str_contains($mail->bodyText, 'Hello Asha from Sunrise Library')
                && str_contains($mail->bodyText, 'Code SUN001');
        });
    }

    public function test_fee_due_alert_uses_custom_template(): void
    {
        Mail::fake();
        PlatformSetting::current()->update([
            'email_fee_alerts_enabled' => true,
            'email_template_overrides' => [
                'fee_due_today' => [
                    'subject' => 'Pay {amount} today',
                    'body' => 'Dear {name}, please pay {amount} by {due_date} at {library}.',
                    'headline' => 'Custom fee due',
                ],
            ],
        ]);

        $branch = Branch::factory()->create(['name' => 'Main']);
        $hall = Hall::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['hall_id' => $hall->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'Asha',
            'email' => 'asha@example.com',
        ]);
        $booking = SeatBooking::create([
            'student_id' => $student->id,
            'seat_id' => $seat->id,
            'time_slot' => 'full_day',
            'status' => 'active',
            'fee_amount' => 3000,
            'amount_paid' => 0,
            'joining_date' => now()->toDateString(),
            'plan_expiry_date' => now()->addMonth()->toDateString(),
            'fee_type' => 'monthly',
            'payment_plan' => 'installments',
        ]);
        $installment = FeeInstallment::create([
            'seat_booking_id' => $booking->id,
            'installment_number' => 1,
            'amount' => 1500,
            'due_date' => now()->toDateString(),
            'paid_at' => null,
        ]);

        $sent = app(FeeAlertService::class)->sendInstallmentAlert($installment, [
            'key' => 'fee_due_today',
            'subject' => 'ignored',
            'headline' => 'ignored',
        ]);

        $this->assertTrue($sent);
        Mail::assertSent(AutomationAlertMail::class, function (AutomationAlertMail $mail) {
            return $mail->subjectLine === 'Pay 1,500.00 today'
                && $mail->headline === 'Custom fee due'
                && str_contains($mail->bodyText, 'Dear Asha, please pay 1,500.00');
        });
    }

    public function test_settings_can_save_and_reset_template(): void
    {
        $user = User::factory()->create(['branch_id' => null, 'email' => 'owner@example.com']);
        Admin::query()->create(['user_id' => $user->id, 'admin_type' => Admin::TYPE_CLIENT]);

        $this->actingAs($user)
            ->putJson(route('settings.email-templates.update', 'welcome'), [
                'subject' => 'Custom welcome {name}',
                'body' => 'Body for {library}',
            ])
            ->assertOk()
            ->assertJsonPath('template.customized', true)
            ->assertJsonPath('template.subject', 'Custom welcome {name}');

        $this->actingAs($user)
            ->postJson(route('settings.email-templates.reset', 'welcome'))
            ->assertOk()
            ->assertJsonPath('template.customized', false)
            ->assertJsonPath('template.subject', 'Welcome to {library}');
    }
}
