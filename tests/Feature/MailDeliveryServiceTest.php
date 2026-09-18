<?php

namespace Tests\Feature;

use App\Services\MailDeliveryService;
use Tests\TestCase;

class MailDeliveryServiceTest extends TestCase
{
    public function test_log_mailer_is_not_configured_for_delivery(): void
    {
        config(['mail.default' => 'log']);

        $service = app(MailDeliveryService::class);

        $this->assertFalse($service->isConfigured());
        $this->assertStringContainsString('log', (string) $service->issue());
    }

    public function test_smtp_without_credentials_is_not_configured(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => '',
            'mail.mailers.smtp.password' => '',
            'mail.from.address' => 'library@example.com',
        ]);

        $service = app(MailDeliveryService::class);

        $this->assertFalse($service->isConfigured());
        $this->assertStringContainsString('MAIL_USERNAME', (string) $service->issue());
    }

    public function test_smtp_with_credentials_is_configured(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.username' => 'library@example.com',
            'mail.mailers.smtp.password' => 'secret',
            'mail.from.address' => 'library@example.com',
        ]);

        $service = app(MailDeliveryService::class);

        $this->assertTrue($service->isConfigured());
        $this->assertNull($service->issue());
    }
}
