<?php

namespace App\Services\Tenancy;

use App\Mail\AutomationAlertMail;
use App\Models\EmailAutomationLog;
use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Owner emails, read-only lock and final deletion for self-service trial libraries.
 */
class TrialLifecycle
{
    public function __construct(
        private TenantConnectionManager $connections,
        private TrialProvisioner $trials,
    ) {}

    public function verificationUrl(Tenant $tenant): string
    {
        $path = URL::temporarySignedRoute(
            'trials.verify',
            now()->addDays(7),
            ['tenant' => $tenant->id, 'hash' => sha1((string) $tenant->owner_email)],
            absolute: false,
        );

        return rtrim((string) config('app.url'), '/').$path;
    }

    public function upgradeUrl(Tenant $tenant): string
    {
        return 'https://'.$tenant->host().'/billing';
    }

    public function hubLoginUrl(): string
    {
        $base = rtrim((string) (
            config('libcontrol.public_url')
            ?: config('app.url')
            ?: ('https://'.config('libcontrol.tenancy.base_domain', 'libcontrol.in'))
        ), '/');

        return $base.'/admin/login';
    }

    public function sendVerification(Tenant $tenant): bool
    {
        $hours = (int) config('libcontrol.trials.verify_hours', 24);

        return $this->mail(
            $tenant,
            'Verify your email for '.$tenant->client_name,
            'Confirm your email address',
            "Hi {$this->firstName($tenant)},\n\nThanks for starting your free LibControl trial at {$tenant->host()}.\n\nPlease confirm this is your email address. If it isn't verified within {$hours} hours, your library is paused until you do.",
            'Verify my email',
            $this->verificationUrl($tenant),
        );
    }

    /**
     * Sent once when the trial library is ready. Includes the hub login URL (email + password).
     */
    public function sendWelcome(Tenant $tenant): bool
    {
        if (! $tenant->isReady() || blank($tenant->owner_email)) {
            return false;
        }

        if ($this->logs($tenant)->where('template_key', 'trial_welcome')->exists()) {
            return true;
        }

        $loginUrl = $this->hubLoginUrl();
        $libraryUrl = $tenant->url();
        $sent = $this->mail(
            $tenant,
            'Welcome to LibControl — '.$tenant->client_name.' is ready',
            'Your library is ready',
            "Hi {$this->firstName($tenant)},\n\nWelcome to LibControl. {$tenant->client_name} is live.\n\n"
            ."Your library: {$libraryUrl}\n\n"
            ."Log in with the email and password you chose at signup:\n{$loginUrl}",
            'Log in to your library',
            $loginUrl,
        );

        if (! $sent) {
            return false;
        }

        $this->logs($tenant)->create([
            'template_key' => 'trial_welcome',
            'recipient_email' => (string) $tenant->owner_email,
            'related_type' => Tenant::class,
            'related_id' => $tenant->id,
            'sent_at' => now(),
        ]);

        return true;
    }

    public function markVerified(Tenant $tenant): void
    {
        if ($tenant->owner_email_verified_at !== null) {
            return;
        }

        $tenant->forceFill(['owner_email_verified_at' => now()])->save();

        if (! $tenant->isReady() || $tenant->database_name === '') {
            return;
        }

        try {
            $this->connections->runOnTenant($tenant, fn () => User::query()
                ->where('email', $tenant->owner_email)
                ->whereNull('email_verified_at')
                ->update(['email_verified_at' => now()]));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return list<string> what was done (or would be done with $dryRun)
     */
    public function run(bool $dryRun = false): array
    {
        $log = [];

        Tenant::query()
            ->where('is_trial', true)
            ->where('status', Tenant::STATUS_READY)
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($dryRun, &$log) {
                foreach ($this->process($tenant, $dryRun) as $action) {
                    $log[] = $tenant->host().': '.$action;
                }
            });

        return $log;
    }

    /**
     * @return list<string>
     */
    private function process(Tenant $tenant, bool $dryRun): array
    {
        $done = [];
        $now = now();

        if ($deadline = $tenant->verificationDeadline()) {
            if ($deadline->isPast()) {
                $this->once($tenant, 'trial_verify_paused', $dryRun, $done, fn () => $this->mail(
                    $tenant,
                    'Your library is paused: please verify your email',
                    'Verify your email to continue',
                    "Hi {$this->firstName($tenant)},\n\nWe couldn't confirm your email address, so {$tenant->host()} is paused. Your data is safe. Verify your email and you can carry on straight away.",
                    'Verify my email',
                    $this->verificationUrl($tenant),
                ));
            } elseif ($now->gte($deadline->copy()->subHours(12))) {
                $this->once($tenant, 'trial_verify_reminder', $dryRun, $done, fn () => $this->mail(
                    $tenant,
                    'Reminder: verify your email for '.$tenant->client_name,
                    'Please confirm your email address',
                    "Hi {$this->firstName($tenant)},\n\nYour library {$tenant->host()} will be paused in about ".max(1, (int) ceil($now->diffInHours($deadline))).' hours unless you verify your email.',
                    'Verify my email',
                    $this->verificationUrl($tenant),
                ));
            }
        }

        if ($tenant->locked_at === null) {
            $endsAt = $tenant->trial_ends_at;

            if ($endsAt === null) {
                return $done;
            }

            $cycle = $endsAt->toDateString();

            if ($endsAt->isPast()) {
                $done[] = 'locked (read-only)';

                if (! $dryRun) {
                    $tenant->forceFill(['locked_at' => $now])->save();
                }

                $deleteOn = $tenant->deletesAt() ?? $now->copy()->addDays((int) config('libcontrol.trials.delete_after_lock_days', 30));
                $this->once($tenant, "trial_locked:{$cycle}", $dryRun, $done, fn () => $this->mail(
                    $tenant,
                    'Your LibControl trial has ended',
                    'Your free trial has ended',
                    "Hi {$this->firstName($tenant)},\n\nYour free trial of {$tenant->host()} has ended, so the library is now read-only. You can still log in and see everything, but you can't add or change anything.\n\nUpgrade to keep using it. If the library isn't upgraded, it and all its data will be deleted on {$this->date($deleteOn)}.",
                    'Upgrade my library',
                    $this->upgradeUrl($tenant),
                ));
            } elseif ($now->gte($endsAt->copy()->subDay())) {
                $this->once($tenant, "trial_ending_1d:{$cycle}", $dryRun, $done, fn () => $this->endingMail($tenant, 'tomorrow'));
            } elseif ($now->gte($endsAt->copy()->subDays(3))) {
                $this->once($tenant, "trial_ending_3d:{$cycle}", $dryRun, $done, fn () => $this->endingMail($tenant, 'in 3 days'));
            }

            return $done;
        }

        $deletesAt = $tenant->deletesAt();
        $cycle = $tenant->locked_at->toDateString();
        $finalKey = "trial_delete_3d:{$cycle}";

        if ($now->gte($deletesAt->copy()->subDays(3))) {
            $finalWarning = $this->logs($tenant)->where('template_key', $finalKey)->first();

            if ($finalWarning === null) {
                $this->once($tenant, $finalKey, $dryRun, $done, fn () => $this->deletionMail($tenant, $deletesAt->max($now->copy()->addDays(3))));
            } elseif ($now->gte($deletesAt) && $finalWarning->sent_at->lte($now->copy()->subDays(3))) {
                $done[] = 'deleted';

                if (! $dryRun) {
                    $this->delete($tenant);
                }
            }

            return $done;
        }

        if ($now->gte($deletesAt->copy()->subDays(14))) {
            $this->once($tenant, "trial_delete_14d:{$cycle}", $dryRun, $done, fn () => $this->deletionMail($tenant, $deletesAt));
        }

        return $done;
    }

    private function delete(Tenant $tenant): void
    {
        $this->trials->rollback($tenant, new \RuntimeException('Trial ended without upgrade; deleted after the grace period.'));
        $tenant->refresh();

        if ($tenant->hosting_database_created || $tenant->hosting_subdomain_created) {
            Log::error('Trial deletion incomplete', ['tenant' => $tenant->id, 'host' => $tenant->host(), 'error' => $tenant->provision_error]);

            return;
        }

        Log::warning('Trial library deleted', ['tenant' => $tenant->id, 'host' => $tenant->host(), 'owner' => $tenant->owner_email]);
        $tenant->delete();
    }

    private function endingMail(Tenant $tenant, string $when): bool
    {
        return $this->mail(
            $tenant,
            "Your LibControl trial ends {$when}",
            "Your free trial ends {$when}",
            "Hi {$this->firstName($tenant)},\n\nYour free trial of {$tenant->host()} ends on {$this->date($tenant->trial_ends_at)}. After that the library becomes read-only until you upgrade.\n\nUpgrade now to keep adding students, seats and fees without interruption.",
            'Upgrade my library',
            $this->upgradeUrl($tenant),
        );
    }

    private function deletionMail(Tenant $tenant, Carbon $deleteOn): bool
    {
        return $this->mail(
            $tenant,
            "Your library {$tenant->host()} will be deleted on {$this->date($deleteOn)}",
            'Your library is scheduled for deletion',
            "Hi {$this->firstName($tenant)},\n\nYour free trial of {$tenant->host()} ended and the library hasn't been upgraded. On {$this->date($deleteOn)} we will permanently delete it, including all students, seats and fee records.\n\nUpgrade before then to keep everything.",
            'Upgrade my library',
            $this->upgradeUrl($tenant),
        );
    }

    /**
     * @param  list<string>  $done
     */
    private function once(Tenant $tenant, string $key, bool $dryRun, array &$done, Closure $send): void
    {
        if ($this->logs($tenant)->where('template_key', $key)->exists()) {
            return;
        }

        $done[] = "email {$key}";

        if ($dryRun || ! $send()) {
            return;
        }

        $this->logs($tenant)->create([
            'template_key' => $key,
            'recipient_email' => (string) $tenant->owner_email,
            'related_type' => Tenant::class,
            'related_id' => $tenant->id,
            'sent_at' => now(),
        ]);
    }

    /**
     * @return Builder<EmailAutomationLog>
     */
    private function logs(Tenant $tenant)
    {
        return EmailAutomationLog::on($tenant->getConnectionName())
            ->where('related_type', Tenant::class)
            ->where('related_id', $tenant->id);
    }

    private function mail(Tenant $tenant, string $subject, string $headline, string $body, string $ctaLabel, string $ctaUrl): bool
    {
        $body .= "\n\nQuestions? Email us at {$this->supportEmail()}.";

        try {
            Mail::to((string) $tenant->owner_email)->send(new AutomationAlertMail(
                subjectLine: $subject,
                headline: $headline,
                bodyText: $body,
                libraryName: 'LibControl',
                ctaLabel: $ctaLabel,
                ctaUrl: $ctaUrl,
            ));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    private function firstName(Tenant $tenant): string
    {
        return strtok(trim((string) $tenant->owner_name), ' ') ?: 'there';
    }

    private function date(?Carbon $date): string
    {
        return $date?->copy()->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))->format('j M Y') ?? '';
    }

    private function supportEmail(): string
    {
        return (string) (config('libcontrol.support.public_email') ?: 'support@libcontrol.in');
    }
}
