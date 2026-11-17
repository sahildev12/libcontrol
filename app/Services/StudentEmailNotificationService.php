<?php

namespace App\Services;

use App\Mail\StudentNotificationMail;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Services\EmailAutomation\EmailTemplateService;
use Illuminate\Support\Facades\Mail;

class StudentEmailNotificationService
{
    public function __construct(
        private EmailTemplateService $templates,
    ) {}

    public function sendWelcome(Student $student): bool
    {
        return $this->sendIfEnabled($student, 'welcome');
    }

    public function sendBirthday(Student $student): bool
    {
        return $this->sendIfEnabled($student, 'birthday');
    }

    public function sendOffer(Student $student, string $subject, string $body): bool
    {
        if (! $this->canSend($student, 'offers')) {
            return false;
        }

        return $this->deliver($student, $subject, $body, 'offers');
    }

    public function sendRecovery(Student $student): bool
    {
        return $this->sendIfEnabled($student, 'recovery');
    }

    private function sendIfEnabled(Student $student, string $type): bool
    {
        if (! $this->canSend($student, $type)) {
            return false;
        }

        $library = PlatformSetting::current()->displayName();
        $resolved = $this->templates->resolve($type, [
            'name' => $student->name ?: 'Student',
            'library' => $library,
            'student_code' => (string) ($student->student_code ?: ''),
        ]);

        return $this->deliver($student, $resolved['subject'], $resolved['body'], $type);
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
}
