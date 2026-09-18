<?php

namespace App\Services;

class MailDeliveryService
{
    public function isConfigured(): bool
    {
        return $this->issue() === null;
    }

    public function issue(): ?string
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return "Email delivery is not configured. MAIL_MAILER is set to \"{$mailer}\", so messages are only saved locally and are not sent to students. Configure SMTP in your server .env file.";
        }

        if ($mailer === 'smtp') {
            $username = trim((string) config('mail.mailers.smtp.username'));
            $password = trim((string) config('mail.mailers.smtp.password'));
            $from = trim((string) config('mail.from.address'));

            if ($username === '' || $password === '') {
                return 'SMTP is not configured. Add MAIL_USERNAME and MAIL_PASSWORD to your server .env file.';
            }

            if ($from === '' || $from === 'hello@example.com') {
                return 'Set a valid MAIL_FROM_ADDRESS in your server .env file before sending emails.';
            }
        }

        return null;
    }

    /**
     * @return array{configured: bool, mailer: string, message: string|null}
     */
    public function status(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'mailer' => (string) config('mail.default'),
            'message' => $this->issue(),
        ];
    }
}
