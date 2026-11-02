<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Services\Hosting\HostingProvider;
use Closure;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrialProvisioner
{
    public const STEPS = ['subdomain', 'database', 'migrate', 'admin', 'ssl', 'done'];

    public const STALE_MINUTES = 10;

    private Closure $sleeper;

    public function __construct(
        private HostingProvider $hosting,
        private TenantProvisioner $provisioner,
    ) {
        $this->sleeper = static fn (int $seconds) => sleep($seconds);
    }

    public function setSleeper(Closure $sleeper): void
    {
        $this->sleeper = $sleeper;
    }

    /**
     * @throws ValidationException
     */
    public function normalizeAndValidateSubdomain(string $raw): string
    {
        $subdomain = Tenant::normalizeSubdomain($raw);

        $error = match (true) {
            strlen($subdomain) < 3 || strlen($subdomain) > 30 => 'Use 3 to 30 letters, numbers or hyphens.',
            ! preg_match('/^[a-z][a-z0-9-]*[a-z0-9]$/', $subdomain) => 'Start with a letter and use only letters, numbers or hyphens.',
            in_array($subdomain, config('libcontrol.trials.reserved_subdomains', []), true) => 'This name is reserved. Please choose another.',
            Tenant::query()->where('subdomain', $subdomain)->exists() => 'This name is already taken. Please choose another.',
            $this->hosting->subdomainExists($subdomain) => 'This name is already taken. Please choose another.',
            default => null,
        };

        if ($error) {
            throw ValidationException::withMessages(['subdomain' => $error]);
        }

        return $subdomain;
    }

    /**
     * @param  array{library_name: string, subdomain: string, owner_name: string, owner_email: string, password?: ?string, source?: string, ip?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function register(array $data): Tenant
    {
        $subdomain = $this->normalizeAndValidateSubdomain($data['subdomain']);

        if ($this->hosting->databaseCount() >= (int) config('libcontrol.trials.max_databases', 280)) {
            throw ValidationException::withMessages([
                'subdomain' => 'New trials are paused right now. Please contact support and we will set you up.',
            ]);
        }

        $source = $data['source'] ?? 'email';
        $email = Str::lower(trim($data['owner_email']));

        $existing = Tenant::query()
            ->where('owner_email', $email)
            ->where('is_trial', true)
            ->where('status', '!=', Tenant::STATUS_FAILED)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => "You already have a trial library at {$existing->host()}. Please log in there.",
            ]);
        }

        return Tenant::query()->create([
            'subdomain' => $subdomain,
            'client_name' => trim($data['library_name']),
            'owner_name' => trim($data['owner_name']),
            'owner_email' => $email,
            'owner_password_hash' => Hash::make(filled($data['password'] ?? null) ? $data['password'] : Str::random(40)),
            'owner_email_verified_at' => $source === 'email' ? null : now(),
            'signup_source' => $source,
            'database_name' => '',
            'plan_tier' => (string) config('libcontrol.trials.plan_tier', 'pro'),
            'is_trial' => true,
            'trial_ends_at' => now()->addDays((int) config('libcontrol.trials.days', 14)),
            'active' => false,
            'status' => Tenant::STATUS_PENDING,
            'signup_ip' => $data['ip'] ?? null,
            'signup_token' => Str::random(48),
        ]);
    }

    /**
     * Atomically marks the trial as provisioning so concurrent callers cannot build it twice.
     */
    public function claim(Tenant $tenant): bool
    {
        $claimed = Tenant::query()
            ->whereKey($tenant->id)
            ->where(fn ($query) => $query
                ->whereIn('status', [Tenant::STATUS_PENDING, Tenant::STATUS_FAILED])
                ->orWhere(fn ($stale) => $stale
                    ->where('status', Tenant::STATUS_PROVISIONING)
                    ->where('updated_at', '<', now()->subMinutes(self::STALE_MINUTES))))
            ->update(['status' => Tenant::STATUS_PROVISIONING, 'provision_error' => null, 'updated_at' => now()]);

        $tenant->refresh();

        return $claimed === 1;
    }

    public function provision(Tenant $tenant): Tenant
    {
        if (! $this->claim($tenant)) {
            return $tenant;
        }

        @set_time_limit(600);
        ignore_user_abort(true);

        return $this->withoutSignedInUser(fn () => $this->build($tenant));
    }

    private function build(Tenant $tenant): Tenant
    {
        try {
            $this->step($tenant, 'subdomain');
            if (! $tenant->hosting_subdomain_created) {
                $this->hosting->createSubdomain($tenant->subdomain);
                $tenant->update(['hosting_subdomain_created' => true]);
            }

            $this->step($tenant, 'database');
            if (! $tenant->hosting_database_created) {
                $password = Str::password(20, symbols: false).'Aa9';
                $created = $this->hosting->createDatabase('lc'.$tenant->id.Str::lower(Str::random(4)), $password);
                $tenant->update([
                    'database_name' => $created['database'],
                    'db_username' => $created['username'],
                    'db_password' => $created['username'] ? $password : null,
                    'hosting_database_created' => true,
                ]);
            }
            $this->waitFor(
                fn () => $this->provisioner->canConnect($tenant),
                (int) config('libcontrol.trials.database_wait_seconds', 60),
                'The new database did not become reachable in time.',
            );

            $this->step($tenant, 'migrate');
            $this->provisioner->migrateTenant($tenant);

            $this->step($tenant, 'admin');
            $this->provisioner->provisionWithPasswordHash(
                $tenant,
                (string) $tenant->owner_email,
                (string) ($tenant->owner_password_hash ?: Hash::make(Str::random(40))),
                (string) ($tenant->owner_name ?: 'Library Admin'),
                emailVerified: $tenant->owner_email_verified_at !== null,
                migrate: false,
            );
            $tenant->update(['owner_password_hash' => null]);

            $this->step($tenant, 'ssl');
            $sslReady = $this->poll(
                fn () => $this->hosting->sslActive($tenant->subdomain),
                (int) config('libcontrol.trials.ssl_wait_seconds', 180),
            );

            $tenant->update([
                'status' => Tenant::STATUS_READY,
                'provision_step' => 'done',
                'active' => true,
                'provision_error' => $sslReady ? null : 'SSL was still installing when the trial went live.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Trial provisioning failed', ['tenant' => $tenant->id, 'step' => $tenant->provision_step, 'error' => $e->getMessage()]);
            $this->rollback($tenant, $e);
        }

        return $tenant->refresh();
    }

    /**
     * Removes only the hosting resources this trial created.
     */
    public function rollback(Tenant $tenant, ?\Throwable $reason = null): void
    {
        $errors = [];

        if ($tenant->hosting_database_created && $tenant->database_name !== '') {
            try {
                $this->hosting->deleteDatabase($tenant->database_name);
                $tenant->update(['hosting_database_created' => false]);
            } catch (\Throwable $e) {
                $errors[] = 'database cleanup: '.$e->getMessage();
            }
        }

        if ($tenant->hosting_subdomain_created) {
            try {
                $this->hosting->deleteSubdomain($tenant->subdomain);
                $tenant->update(['hosting_subdomain_created' => false]);
            } catch (\Throwable $e) {
                $errors[] = 'subdomain cleanup: '.$e->getMessage();
            }
        }

        $tenant->update([
            'status' => Tenant::STATUS_FAILED,
            'active' => false,
            'provision_error' => trim(($reason?->getMessage() ?? 'Rolled back').' '.implode('; ', $errors)),
        ]);
    }

    /**
     * Whoever's browser triggers the build may be signed in on the main site; that user
     * does not exist in the new library, so nothing may be recorded against them.
     */
    private function withoutSignedInUser(Closure $callback): Tenant
    {
        $auth = app('auth');
        $previous = $auth->getDefaultDriver();

        $auth->extend('trial-provisioning', fn () => new RequestGuard(fn () => null, request()));
        config(['auth.guards.trial-provisioning' => ['driver' => 'trial-provisioning']]);
        $auth->shouldUse('trial-provisioning');

        try {
            return $callback();
        } finally {
            $auth->shouldUse($previous);
        }
    }

    private function step(Tenant $tenant, string $step): void
    {
        $tenant->update(['provision_step' => $step]);
    }

    private function waitFor(Closure $check, int $seconds, string $message): void
    {
        if (! $this->poll($check, $seconds)) {
            throw new \RuntimeException($message);
        }
    }

    private function poll(Closure $check, int $seconds, int $interval = 3): bool
    {
        $attempts = max(1, (int) ceil($seconds / $interval));

        for ($i = 0; $i < $attempts; $i++) {
            if ($check()) {
                return true;
            }

            ($this->sleeper)($interval);
        }

        return (bool) $check();
    }
}
