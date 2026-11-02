<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenancy\TrialProvisioner;
use Illuminate\Console\Command;

class ResumePendingTrials extends Command
{
    protected $signature = 'libcontrol:trial-resume';

    protected $description = 'Build trial libraries whose sign-up page never started the build, or whose build stalled';

    public function handle(TrialProvisioner $trials): int
    {
        $tenants = Tenant::query()
            ->where('is_trial', true)
            ->where(fn ($query) => $query
                ->where(fn ($pending) => $pending
                    ->where('status', Tenant::STATUS_PENDING)
                    ->where('created_at', '<', now()->subMinute()))
                ->orWhere(fn ($stale) => $stale
                    ->where('status', Tenant::STATUS_PROVISIONING)
                    ->where('updated_at', '<', now()->subMinutes(TrialProvisioner::STALE_MINUTES))))
            ->orderBy('id')
            ->limit(3)
            ->get();

        foreach ($tenants as $tenant) {
            $tenant = $trials->provision($tenant);
            $this->line("{$tenant->host()}: {$tenant->status}");
        }

        return self::SUCCESS;
    }
}
