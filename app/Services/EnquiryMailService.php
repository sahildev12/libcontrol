<?php

namespace App\Services;

use App\Mail\AutomationAlertMail;
use App\Models\Enquiry;
use App\Models\PlatformSetting;
use App\Services\EmailAutomation\OwnerRecipientResolver;
use Illuminate\Support\Facades\Mail;

class EnquiryMailService
{
    public function __construct(
        private OwnerRecipientResolver $owners,
    ) {}

    public function notifyNewEnquiry(Enquiry $enquiry): void
    {
        $libraryName = PlatformSetting::current()->displayName();

        $this->notifyLibraryAdmins($enquiry, $libraryName);
        $this->confirmToEnquirer($enquiry, $libraryName);
    }

    private function notifyLibraryAdmins(Enquiry $enquiry, string $libraryName): void
    {
        $emails = $this->owners->clientAdminEmails();
        if ($emails === []) {
            return;
        }

        $context = array_filter([
            'Name' => $enquiry->name,
            'Phone' => $enquiry->phone ?: null,
            'Email' => $enquiry->email ?: null,
            'Message' => $enquiry->message ?: null,
        ], fn ($value) => filled($value));

        try {
            Mail::to($emails)->send(new AutomationAlertMail(
                subjectLine: "New enquiry from {$enquiry->name}",
                headline: 'New website enquiry',
                bodyText: "{$enquiry->name} sent an enquiry through your library website. Open Enquiries to follow up.",
                libraryName: $libraryName,
                context: $context,
                ctaLabel: 'View enquiries',
                ctaUrl: route('enquiries.index'),
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function confirmToEnquirer(Enquiry $enquiry, string $libraryName): void
    {
        $email = trim((string) $enquiry->email);
        if ($email === '') {
            return;
        }

        $settings = PlatformSetting::current();
        $firstName = strtok(trim((string) $enquiry->name), ' ') ?: 'there';
        $supportBits = array_filter([
            $settings->website_whatsapp
                ? 'WhatsApp: '.$settings->website_whatsapp
                : null,
            $settings->website_address
                ? 'Address: '.$settings->website_address
                : null,
        ]);

        $body = "Hi {$firstName},\n\n"
            ."Thanks for contacting {$libraryName}. We received your enquiry and will get back to you soon.";

        if ($supportBits !== []) {
            $body .= "\n\n".implode("\n", $supportBits);
        }

        try {
            Mail::to($email)->send(new AutomationAlertMail(
                subjectLine: "We received your enquiry — {$libraryName}",
                headline: 'Enquiry received',
                bodyText: $body,
                libraryName: $libraryName,
                context: array_filter([
                    'Your phone' => $enquiry->phone ?: null,
                    'Your message' => $enquiry->message ?: null,
                ], fn ($value) => filled($value)),
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
