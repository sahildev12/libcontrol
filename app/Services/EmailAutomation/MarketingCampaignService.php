<?php

namespace App\Services\EmailAutomation;

use App\Mail\AutomationAlertMail;
use App\Models\PlatformSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MarketingCampaignService
{
    /**
     * @var list<array{key: string, subject: string, headline: string, body: string}>
     */
    public const WEEKLY_CADENCE = [
        [
            'key' => 'marketing_week_1_product',
            'subject' => 'LibControl Update: What’s New This Month',
            'headline' => 'Product updates',
            'body' => 'New tools are landing to help you manage seats, fees, and student engagement with less manual work.',
        ],
        [
            'key' => 'marketing_week_2_tips',
            'subject' => 'Operator Tip: Keep Your Seat Map Fresh',
            'headline' => 'Operator tip',
            'body' => 'Review vacant and expiring seats every Monday morning — libraries that do this consistently fill seats faster.',
        ],
        [
            'key' => 'marketing_week_3_story',
            'subject' => 'How Libraries Grow With LibControl',
            'headline' => 'Customer story',
            'body' => 'Owners who track occupancy and fee follow-ups weekly report steadier renewals and fewer surprise vacancies.',
        ],
        [
            'key' => 'marketing_week_4_invite',
            'subject' => 'Invite a Fellow Library Owner',
            'headline' => 'Share LibControl',
            'body' => 'Know another library that still runs on notebooks or spreadsheets? Share LibControl and grow together.',
        ],
    ];

    public function __construct(
        private EmailAutomationLogger $logger,
        private OwnerRecipientResolver $owners,
    ) {}

    public function sendScheduledCampaign(): int
    {
        $settings = PlatformSetting::current();
        if (! $settings->email_marketing_enabled) {
            return 0;
        }

        $emails = $this->owners->clientAdminEmails();
        if ($emails === []) {
            return 0;
        }

        $token = $settings->marketing_unsubscribe_token ?: Str::random(40);
        if (! $settings->marketing_unsubscribe_token) {
            $settings->forceFill(['marketing_unsubscribe_token' => $token])->save();
        }

        $today = Carbon::today(config('libcontrol.timezone', 'Asia/Kolkata'));
        $weekIndex = ((int) $today->format('W')) % count(self::WEEKLY_CADENCE);
        $template = self::WEEKLY_CADENCE[$weekIndex];
        $templateKey = $template['key'].'_'.$today->format('o-\WW');

        if ($this->logger->wasSent($templateKey, PlatformSetting::class, (int) $settings->id)) {
            return 0;
        }

        // Monthly highlight on the first Thursday-ish send of the month.
        if ((int) $today->format('j') <= 7) {
            $monthlyKey = 'marketing_monthly_'.$today->format('Y-m');
            if (! $this->logger->wasSent($monthlyKey, PlatformSetting::class, (int) $settings->id)) {
                $template = [
                    'key' => $monthlyKey,
                    'subject' => 'Your Monthly LibControl Digest',
                    'headline' => 'Monthly digest',
                    'body' => 'Here is your monthly product and growth digest. Stay opted in to keep receiving tips and updates.',
                ];
                $templateKey = $monthlyKey;
            }
        }

        $libraryName = $settings->displayName();
        $unsubscribeUrl = url('/email/unsubscribe/'.$token);

        try {
            Mail::to($emails)->send(new AutomationAlertMail(
                subjectLine: $template['subject'],
                headline: $template['headline'],
                bodyText: "Hi,\n\n".$template['body']."\n\nYou can unsubscribe anytime: {$unsubscribeUrl}",
                libraryName: $libraryName,
                context: [
                    'Library' => $libraryName,
                ],
                ctaLabel: 'Open LibControl',
                ctaUrl: url('/dashboard'),
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return 0;
        }

        return $this->logger->record(
            $templateKey,
            implode(',', $emails),
            PlatformSetting::class,
            (int) $settings->id,
            null,
            ['unsubscribe_token' => $token],
        ) ? 1 : 0;
    }

    public function unsubscribe(string $token): bool
    {
        $settings = PlatformSetting::current();
        if (! hash_equals((string) $settings->marketing_unsubscribe_token, $token)) {
            return false;
        }

        $settings->forceFill([
            'email_marketing_enabled' => false,
            'email_growth_enabled' => false,
        ])->save();

        return true;
    }
}
