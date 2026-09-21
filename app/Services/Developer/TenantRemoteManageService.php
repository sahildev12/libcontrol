<?php

namespace App\Services\Developer;

use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Addons\AddonRegistry;
use App\Services\DatabaseMaintenanceService;
use App\Services\Tenancy\TenantConnectionManager;
use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TenantRemoteManageService
{
    public function __construct(
        private TenantConnectionManager $connections,
        private TenantProvisioner $provisioner,
        private AddonRegistry $addonRegistry,
        private DatabaseMaintenanceService $databaseMaintenance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function managePayload(Tenant $tenant): array
    {
        $planTiers = array_keys(config('libcontrol.plans', []));
        $addons = [];
        $backups = [];

        if ($tenant->provisioned_at) {
            $addons = $this->connections->runOnTenant($tenant, fn () => $this->addonRegistry->catalogForSettings());
            $backups = $this->connections->runOnTenant($tenant, fn () => $this->databaseMaintenance->listBackups());
        }

        return [
            'tenant' => $tenant,
            'planTiers' => $planTiers,
            'planSnapshot' => $this->planSnapshotForTenant($tenant),
            'availableAddons' => $addons,
            'backups' => $backups,
        ];
    }

    /**
     * @param  array<string, mixed>  $planData
     */
    public function updatePlan(Tenant $tenant, array $planData, ?User $user, ?Request $request): Tenant
    {
        $tenant->update([
            'plan_tier' => $planData['plan_tier'],
            'max_seats_override' => $planData['max_seats_override'] ?? null,
            'max_halls_override' => $planData['max_halls_override'] ?? null,
            'max_branches_override' => $planData['max_branches_override'] ?? null,
        ]);

        $this->provisioner->syncPlan($tenant->fresh());

        app(ActivityLogger::class)->record(
            $user,
            'tenant.plan_updated',
            "Updated plan for {$tenant->client_name}.",
            $tenant,
            null,
            ['tenant_id' => $tenant->id],
            $request,
        );

        return $tenant->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message: string}
     */
    public function runAction(Tenant $tenant, string $action, array $payload, ?User $user, ?Request $request): array
    {
        $result = $this->connections->runOnTenant($tenant, function () use ($action, $payload) {
            return match ($action) {
                'clear_cache' => $this->clearCache(),
                'addon_install' => $this->addonAction('install', (string) ($payload['slug'] ?? '')),
                'addon_enable' => $this->addonAction('enable', (string) ($payload['slug'] ?? '')),
                'addon_disable' => $this->addonAction('disable', (string) ($payload['slug'] ?? '')),
                'database_backup' => $this->databaseBackup(),
                'database_migrate' => $this->databaseMigrate(),
                'database_restore' => $this->databaseRestore($payload),
                default => ['status' => 'failed', 'message' => "Unknown action: {$action}"],
            };
        });

        app(ActivityLogger::class)->record(
            $user,
            'tenant.remote_action',
            "Ran {$action} on {$tenant->client_name}.",
            $tenant,
            null,
            ['action' => $action, 'status' => $result['status']],
            $request,
        );

        return $result;
    }

    /**
     * @return array{status: string, message: string}
     */
    private function clearCache(): array
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return ['status' => 'completed', 'message' => 'Application cache cleared.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function addonAction(string $operation, string $slug): array
    {
        if ($slug === '') {
            return ['status' => 'failed', 'message' => 'Addon slug is required.'];
        }

        match ($operation) {
            'install' => $this->addonRegistry->install($slug),
            'enable' => $this->addonRegistry->enable($slug),
            'disable' => $this->addonRegistry->disable($slug),
        };

        return ['status' => 'completed', 'message' => "Addon [{$slug}] {$operation}d."];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function databaseBackup(): array
    {
        $backup = $this->databaseMaintenance->createBackup();

        return ['status' => 'completed', 'message' => 'Created backup '.$backup['filename'].'.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function databaseMigrate(): array
    {
        $result = $this->databaseMaintenance->runMigrations();

        return ['status' => 'completed', 'message' => $result['output'] ?: 'Migrations completed.'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, message: string}
     */
    private function databaseRestore(array $payload): array
    {
        if (($payload['confirmation'] ?? '') !== 'RESTORE') {
            return ['status' => 'failed', 'message' => 'Restore confirmation missing.'];
        }

        $filename = (string) ($payload['filename'] ?? '');
        $this->databaseMaintenance->restoreBackup($filename);

        return ['status' => 'completed', 'message' => "Database restored from {$filename}."];
    }

    /**
     * @return array<string, mixed>
     */
    private function planSnapshotForTenant(Tenant $tenant): array
    {
        $tier = $tenant->planTier();
        $plans = config('libcontrol.plans', []);
        $defaults = $plans[$tier] ?? $plans['starter'] ?? [];

        return [
            'plan_tier' => $tier,
            'plan_label' => $defaults['label'] ?? ucfirst($tier),
            'max_seats' => $tenant->max_seats_override ?? $defaults['max_seats'] ?? null,
            'max_halls' => $tenant->max_halls_override ?? $defaults['max_halls'] ?? null,
            'max_branches' => $tenant->max_branches_override ?? $defaults['max_branches'] ?? null,
        ];
    }
}
