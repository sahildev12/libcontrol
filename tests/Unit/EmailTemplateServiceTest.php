<?php

namespace Tests\Unit;

use App\Models\PlatformSetting;
use App\Services\EmailAutomation\EmailTemplateService;
use App\Support\EmailTemplateCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_uses_defaults_when_no_override(): void
    {
        $resolved = app(EmailTemplateService::class)->resolve('welcome', [
            'name' => 'Asha',
            'library' => 'Sunrise Library',
        ]);

        $this->assertSame('Welcome to Sunrise Library', $resolved['subject']);
        $this->assertStringContainsString('Hi Asha', $resolved['body']);
        $this->assertStringContainsString('Welcome to Sunrise Library', $resolved['body']);
        $this->assertFalse($resolved['customized']);
    }

    public function test_override_subject_and_body_are_used(): void
    {
        PlatformSetting::current()->update([
            'email_template_overrides' => [
                'welcome' => [
                    'subject' => 'Namaste {name} — {library}',
                    'body' => "Hello {name},\n\nYour desk at {library} is ready.",
                ],
            ],
        ]);

        $resolved = app(EmailTemplateService::class)->resolve('welcome', [
            'name' => 'Asha',
            'library' => 'Sunrise Library',
        ]);

        $this->assertSame('Namaste Asha — Sunrise Library', $resolved['subject']);
        $this->assertSame("Hello Asha,\n\nYour desk at Sunrise Library is ready.", $resolved['body']);
        $this->assertTrue($resolved['customized']);
    }

    public function test_reset_clears_only_one_template(): void
    {
        $settings = PlatformSetting::current();
        $settings->update([
            'email_template_overrides' => [
                'welcome' => ['subject' => 'Custom welcome', 'body' => 'Custom body'],
                'birthday' => ['subject' => 'Custom bday', 'body' => 'Custom bday body'],
            ],
        ]);

        app(EmailTemplateService::class)->reset('welcome');

        $overrides = PlatformSetting::current()->email_template_overrides;
        $this->assertArrayNotHasKey('welcome', $overrides);
        $this->assertSame('Custom bday', $overrides['birthday']['subject']);
    }

    public function test_catalog_includes_fee_and_seat_student_templates(): void
    {
        $keys = array_keys(EmailTemplateCatalog::all());

        $this->assertContains('fee_due_today', $keys);
        $this->assertContains('fee_payment_received', $keys);
        $this->assertContains('seat_booking_confirmed', $keys);
        $this->assertContains('seat_expiry_in_1_day', $keys);
    }
}
