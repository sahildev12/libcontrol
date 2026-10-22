<?php

namespace App\Services\EmailAutomation;

use App\Mail\AutomationAlertMail;
use App\Models\LicensedDeployment;
use App\Models\PlatformSetting;
use App\Support\InstallState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SubscriptionTrialService
{
    /**
     * Offsets relative to trial start (negative = before expiry handled separately).
     *
     * @var array<int, array{key: string, subject: string, headline: string}>
     */
    public const FROM_START = [
        0 => [
            'key' => 'saas_trial_welcome',
            'subject' => 'Welcome to Your LibControl Trial',
            'headline' => 'Welcome to your trial',
        ],
        1 => [
            'key' => 'saas_trial_day_1',
            'subject' => 'Day 1 Tip: Set Up Your First Hall',
            'headline' => 'Get started on day 1',
        ],
        3 => [
            'key' => 'saas_trial_day_3',
            'subject' => 'Day 3: Invite Your Team & Add Students',
            'headline' => 'Keep building on day 3',
        ],
        5 => [
            'key' => 'saas_trial_day_5',
            'subject' => 'Day 5: Review Fees & Seat Occupancy',
            'headline' => 'Mid-trial check-in',
        ],
    ];

    /**
     * Offsets relative to trial/plan end (negative = before, 0 = day of, positive = after).
     *
     * @var array<int, array{key: string, subject: string, headline: string}>
     */
    public const FROM_EXPIRY = [
        -3 => [
            'key' => 'saas_trial_3_days_before',
            'subject' => 'Your LibControl Trial Ends in 3 Days',
            'headline' => 'Trial ends in 3 days',
        ],
        -1 => [
            'key' => 'saas_trial_1_day_before',
            'subject' => 'Last Day Tomorrow — Activate Your Plan',
            'headline' => 'Trial ends tomorrow',
        ],
        0 => [
            'key' => 'saas_trial_expiry_day',
            'subject' => 'Your LibControl Trial Ends Today',
            'headline' => 'Trial ends today',
        ],
        2 => [
            'key' => 'saas_trial_2_days_after',
            'subject' => 'We Saved Your Library Data — Reactivate Anytime',
            'headline' => 'Come back to LibControl',
        ],
        5 => [
            'key' => 'saas_trial_5_days_after',
            'subject' => 'Still Thinking It Over? Here’s What You’re Missing',
            'headline' => 'Your library is waiting',
        ],
    ];

    public function __construct(
        private EmailAutomationLogger $logger,
        private OwnerRecipientResolver $owners,
    ) {}

    public function sendScheduledAlerts(): int
    {
        if (InstallState::isHub()) {
            return $this->sendForHubDeployments();
        }

        return $this->sendForLocalInstall();
    }

    private function sendForLocalInstall(): int
    {
        $settings = PlatformSetting::current();
        if (! $settings->email_subscription_alerts_enabled) {
            return 0;
        }

        if ($this->isPaidProOrCustom($settings->planTier(), $settings->plan_paid_until)) {
            return 0;
        }

        $emails = $this->owners->clientAdminEmails();
        if ($emails === []) {
            return 0;
        }

        $trialStart = $settings->subscription_trial_started_at
            ? Carbon::parse($settings->subscription_trial_started_at)->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))->startOfDay()
            : null;
        $trialEnd = $settings->plan_paid_until
            ? Carbon::parse($settings->plan_paid_until)->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))->startOfDay()
            : null;

        if (! $trialStart && ! $trialEnd) {
            return 0;
        }

        if (! $trialStart && $trialEnd) {
            $trialStart = $trialEnd->copy()->subDays(14);
        }

        $ctaUrl = url('/billing');
        $libraryName = $settings->displayName();
        $relatedType = PlatformSetting::class;
        $relatedId = (int) $settings->id;

        return $this->dispatchSequence(
            $emails,
            $libraryName,
            $trialStart,
            $trialEnd,
            $relatedType,
            $relatedId,
            $ctaUrl,
        );
    }

    private function sendForHubDeployments(): int
    {
        $sent = 0;
        $support = config('libcontrol.support.public_email') ?: config('libcontrol.support.email');

        foreach (LicensedDeployment::query()->where('active', true)->cursor() as $deployment) {
            if ($this->isPaidProOrCustom($deployment->planTier(), $deployment->plan_paid_until)) {
                continue;
            }

            $trialEnd = $deployment->plan_paid_until
                ? Carbon::parse($deployment->plan_paid_until)->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))->startOfDay()
                : null;

            if (! $trialEnd) {
                continue;
            }

            $trialStart = $trialEnd->copy()->subDays(14);
            $email = $support;
            if (! $email) {
                continue;
            }

            $name = $deployment->client_name ?: 'LibControl library';
            $ctaUrl = 'https://libcontrol.katsindia.com/billing';

            $sent += $this->dispatchSequence(
                [$email],
                $name,
                $trialStart,
                $trialEnd,
                LicensedDeployment::class,
                (int) $deployment->id,
                $ctaUrl,
            );
        }

        return $sent;
    }

    /**
     * @param  list<string>  $emails
     */
    private function dispatchSequence(
        array $emails,
        string $libraryName,
        Carbon $trialStart,
        ?Carbon $trialEnd,
        string $relatedType,
        int $relatedId,
        string $ctaUrl,
    ): int {
        $today = Carbon::today(config('libcontrol.timezone', 'Asia/Kolkata'));
        $sent = 0;

        foreach (self::FROM_START as $offset => $template) {
            if (! $today->equalTo($trialStart->copy()->addDays($offset))) {
                continue;
            }

            if ($this->sendTemplate($emails, $libraryName, $template, $relatedType, $relatedId, $ctaUrl, $trialEnd)) {
                $sent++;
            }
        }

        if ($trialEnd) {
            foreach (self::FROM_EXPIRY as $offset => $template) {
                if (! $today->equalTo($trialEnd->copy()->addDays($offset))) {
                    continue;
                }

                if ($this->sendTemplate($emails, $libraryName, $template, $relatedType, $relatedId, $ctaUrl, $trialEnd)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * @param  list<string>  $emails
     * @param  array{key: string, subject: string, headline: string}  $template
     */
    private function sendTemplate(
        array $emails,
        string $libraryName,
        array $template,
        string $relatedType,
        int $relatedId,
        string $ctaUrl,
        ?Carbon $trialEnd,
    ): bool {
        if ($this->logger->wasSent($template['key'], $relatedType, $relatedId)) {
            return false;
        }

        $body = "Hi,\n\n"
            ."This is an update about your LibControl subscription trial for {$libraryName}.";

        if ($trialEnd) {
            $body .= "\n\nTrial / plan date: ".$trialEnd->format('M d, Y');
        }

        try {
            Mail::to($emails)->send(new AutomationAlertMail(
                subjectLine: $template['subject'],
                headline: $template['headline'],
                bodyText: $body,
                libraryName: $libraryName,
                context: array_filter([
                    'Library' => $libraryName,
                    'Plan date' => $trialEnd?->format('M d, Y'),
                ]),
                ctaLabel: 'Activate plan',
                ctaUrl: $ctaUrl,
            ));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return $this->logger->record(
            $template['key'],
            implode(',', $emails),
            $relatedType,
            $relatedId,
            null,
            ['trial_end' => $trialEnd?->toDateString()],
        );
    }

    private function isPaidProOrCustom(string $tier, mixed $paidUntil): bool
    {
        if (! in_array($tier, ['pro', 'custom'], true)) {
            return false;
        }

        if (! $paidUntil) {
            return false;
        }

        return Carbon::parse($paidUntil)->isFuture();
    }
}
