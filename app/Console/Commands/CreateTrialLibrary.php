<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenancy\TrialProvisioner;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class CreateTrialLibrary extends Command
{
    protected $signature = 'libcontrol:trial-create
        {subdomain : Library address, e.g. "sunrise" for sunrise.libcontrol.in}
        {email : Owner email (admin login)}
        {--library= : Library display name}
        {--owner= : Owner name}
        {--password= : Owner password (asked if omitted)}
        {--retry : Re-run provisioning for an existing failed or pending trial}';

    protected $description = 'Create a free-trial library: subdomain, database, tables and owner login';

    public function handle(TrialProvisioner $trials): int
    {
        if ($this->option('retry')) {
            $tenant = Tenant::query()->where('subdomain', Tenant::normalizeSubdomain($this->argument('subdomain')))->first();

            if (! $tenant) {
                $this->error('No trial with that subdomain.');

                return self::FAILURE;
            }

            return $this->provision($trials, $tenant);
        }

        $password = $this->option('password') ?: $this->secret('Owner password');

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        try {
            $tenant = $trials->register([
                'library_name' => $this->option('library') ?: ucfirst($this->argument('subdomain')).' Library',
                'subdomain' => $this->argument('subdomain'),
                'owner_name' => $this->option('owner') ?: 'Library Owner',
                'owner_email' => $this->argument('email'),
                'password' => $password,
                'source' => 'email',
            ]);
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->first());

            return self::FAILURE;
        }

        return $this->provision($trials, $tenant);
    }

    private function provision(TrialProvisioner $trials, Tenant $tenant): int
    {
        $this->info("Building {$tenant->host()} ...");
        $started = microtime(true);

        $tenant = $trials->provision($tenant);
        $seconds = round(microtime(true) - $started, 1);

        if (! $tenant->isReady()) {
            $this->error("Failed at step \"{$tenant->provision_step}\" after {$seconds}s: {$tenant->provision_error}");

            return self::FAILURE;
        }

        $this->info("Ready in {$seconds}s: {$tenant->url()} (trial ends {$tenant->trial_ends_at->toDateString()})");

        if ($tenant->provision_error) {
            $this->warn($tenant->provision_error);
        }

        return self::SUCCESS;
    }
}
