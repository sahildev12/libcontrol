<?php

namespace App\Services\EmailAutomation;

use App\Mail\AutomationAlertMail;
use App\Models\PlatformSetting;
use App\Services\DashboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GrowthCampaignService
{
    /**
     * @var list<array{key: string, subject: string, headline: string, body: string, min_days: int}>
     */
    public const STEPS = [
        [
            'key' => 'growth_01_fill_empty_seats',
            'subject' => 'Grow Your Library: Fill Empty Seats This Week',
            'headline' => 'Fill empty seats',
            'body' => 'Run a short renewal or walk-in offer for vacant seats. A simple WhatsApp blast to past enquiries often fills gaps quickly.',
            'min_days' => 0,
        ],
        [
            'key' => 'growth_02_referrals',
            'subject' => 'Grow Your Library: Turn Students Into Referrers',
            'headline' => 'Student referrals',
            'body' => 'Ask active students to refer a friend. Offer a small fee credit when both stay for a full month.',
            'min_days' => 5,
        ],
        [
            'key' => 'growth_03_trial_conversion',
            'subject' => 'Grow Your Library: Convert More Trial Seats',
            'headline' => 'Convert trials',
            'body' => 'Follow up with trial students before day 3. A quick call plus a clear monthly plan closes more seats.',
            'min_days' => 5,
        ],
        [
            'key' => 'growth_04_fee_discipline',
            'subject' => 'Grow Your Library: Tighten Fee Follow-Ups',
            'headline' => 'Fee discipline',
            'body' => 'Use due-date reminders and a fixed collection day. Predictable collections free you to focus on growth.',
            'min_days' => 7,
        ],
        [
            'key' => 'growth_05_visibility',
            'subject' => 'Grow Your Library: Get Found Online',
            'headline' => 'Local visibility',
            'body' => 'Update Google Business hours, photos, and your LibControl public website so nearby students can discover you.',
            'min_days' => 7,
        ],
        [
            'key' => 'growth_06_upsell_slots',
            'subject' => 'Grow Your Library: Upsell Better Time Slots',
            'headline' => 'Premium slots',
            'body' => 'Price peak hours slightly higher and offer off-peak discounts. Better mix often lifts monthly revenue.',
            'min_days' => 14,
        ],
        [
            'key' => 'growth_07_community',
            'subject' => 'Grow Your Library: Build a Student Community',
            'headline' => 'Community loop',
            'body' => 'Host a monthly doubt-clearing or progress check-in. Community habits improve retention and word-of-mouth.',
            'min_days' => 14,
        ],
    ];

    public function __construct(
        private EmailAutomationLogger $logger,
        private OwnerRecipientResolver $owners,
        private DashboardService $dashboardService,
    ) {}

    public function sendScheduledCampaign(): int
    {
        $settings = PlatformSetting::current();
        if (! $settings->email_growth_enabled) {
            return 0;
        }

        if ($settings->plan_paid_until && Carbon::parse($settings->plan_paid_until)->isPast()) {
            return 0;
        }

        $step = (int) ($settings->growth_campaign_step ?? 0);
        if ($step < 0 || $step >= count(self::STEPS)) {
            return 0;
        }

        $template = self::STEPS[$step];
        $lastSent = $settings->growth_last_sent_at
            ? Carbon::parse($settings->growth_last_sent_at)
            : null;

        if ($lastSent && $lastSent->diffInDays(now()) < $template['min_days']) {
            return 0;
        }

        $emails = $this->owners->clientAdminEmails();
        if ($emails === []) {
            return 0;
        }

        if ($this->logger->wasSent($template['key'], PlatformSetting::class, (int) $settings->id)) {
            $settings->forceFill([
                'growth_campaign_step' => $step + 1,
                'growth_last_sent_at' => now(),
            ])->save();

            return 0;
        }

        $libraryName = $settings->displayName();
        $stats = $this->dashboardService->statsForBranch(null);
        $available = (int) ($stats['available'] ?? 0);
        $body = $template['body'];

        if ($available > 0 && Str::contains($template['key'], 'fill_empty')) {
            $body = "You currently have {$available} vacant seat(s).\n\n".$body;
        }

        $token = $settings->marketing_unsubscribe_token ?: Str::random(40);
        if (! $settings->marketing_unsubscribe_token) {
            $settings->forceFill(['marketing_unsubscribe_token' => $token])->save();
        }

        try {
            Mail::to($emails)->send(new AutomationAlertMail(
                subjectLine: $template['subject'],
                headline: $template['headline'],
                bodyText: "Hi,\n\n".$body,
                libraryName: $libraryName,
                context: [
                    'Library' => $libraryName,
                    'Vacant seats' => (string) $available,
                ],
                ctaLabel: 'Open dashboard',
                ctaUrl: url('/dashboard'),
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return 0;
        }

        $recorded = $this->logger->record(
            $template['key'],
            implode(',', $emails),
            PlatformSetting::class,
            (int) $settings->id,
            null,
            ['step' => $step],
        );

        if ($recorded) {
            $settings->forceFill([
                'growth_campaign_step' => $step + 1,
                'growth_last_sent_at' => now(),
            ])->save();
        }

        return $recorded ? 1 : 0;
    }
}
