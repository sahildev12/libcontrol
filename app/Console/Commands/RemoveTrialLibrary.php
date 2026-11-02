<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenancy\TrialProvisioner;
use Illuminate\Console\Command;

class RemoveTrialLibrary extends Command
{
    protected $signature = 'libcontrol:trial-remove {subdomain} {--force : Skip the confirmation prompt}';

    protected $description = 'Permanently delete a trial library: its database, subdomain and registry row';

    public function handle(TrialProvisioner $trials): int
    {
        $tenant = Tenant::query()
            ->where('subdomain', Tenant::normalizeSubdomain($this->argument('subdomain')))
            ->where('is_trial', true)
            ->first();

        if (! $tenant) {
            $this->error('No trial library with that subdomain.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$tenant->host()} and its database {$tenant->database_name}? This cannot be undone.")) {
            return self::FAILURE;
        }

        $trials->rollback($tenant);

        if ($tenant->hosting_database_created || $tenant->hosting_subdomain_created) {
            $this->error("Cleanup incomplete: {$tenant->provision_error}");

            return self::FAILURE;
        }

        $tenant->delete();
        $this->info("Removed {$tenant->host()}.");

        return self::SUCCESS;
    }
}
