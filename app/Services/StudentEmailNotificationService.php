<?php

namespace App\Services;

use App\Mail\StudentNotificationMail;
use App\Models\PlatformSetting;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;

class StudentEmailNotificationService
{
    public function sendWelcome(Student $student): void
    {
        $this->sendIfEnabled($student, 'welcome', 'Welcome to {library}');
    }

    public function sendBirthday(Student $student): void
    {
        $this->sendIfEnabled($student, 'birthday', 'Happy birthday from {library}');
    }

    public function sendOffer(Student $student, string $subject, string $body): bool
    {
        if (! $this->canSend($student, 'offers')) {
            return false;
        }

        return $this->deliver($student, $subject, $body, 'offers');
    }

    public function sendRecovery(Student $student): void
    {
        $this->sendIfEnabled($student, 'recovery', 'We miss you at {library}');
    }

    private function sendIfEnabled(Student $student, string $type, string $subjectTemplate): void
    {
        if (! $this->canSend($student, $type)) {
            return;
        }

        $library = PlatformSetting::current()->displayName();
        $subject = str_replace('{library}', $library, $subjectTemplate);
        $body = $this->defaultBody($type, $student, $library);

        $this->deliver($student, $subject, $body, $type);
    }

    private function canSend(Student $student, string $type): bool
    {
        $email = trim((string) ($student->effectiveEmail() ?: $student->email));

        if ($email === '') {
            return false;
        }

        $settings = PlatformSetting::current();

        return match ($type) {
            'welcome' => (bool) $settings->email_welcome_enabled,
            'birthday' => (bool) $settings->email_birthday_enabled,
            'offers' => (bool) $settings->email_offers_enabled,
            'recovery' => (bool) $settings->email_recovery_enabled,
            default => false,
        };
    }

    private function deliver(Student $student, string $subject, string $body, string $type): bool
    {
        $email = trim((string) ($student->effectiveEmail() ?: $student->email));

        if ($email === '') {
            return false;
        }

        try {
            Mail::to($email)->send(new StudentNotificationMail(
                student: $student,
                subjectLine: $subject,
                bodyText: $body,
                notificationType: $type,
                libraryName: PlatformSetting::current()->displayName(),
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }

    private function defaultBody(string $type, Student $student, string $library): string
    {
        $name = $student->name ?: 'Student';

        return match ($type) {
            'welcome' => "Hi {$name},\n\nWelcome to {$library}. Your student profile has been created successfully.",
            'birthday' => "Hi {$name},\n\nHappy birthday from everyone at {$library}! Wishing you a wonderful year ahead.",
            'recovery' => "Hi {$name},\n\nWe noticed you have been away from {$library} for a while. Visit us again when you are ready.",
            default => "Hi {$name},\n\nThis is a message from {$library}.",
        };
    }
}
