<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Services\Addons\AddonRegistry;
use Illuminate\Support\Facades\Artisan;

class DeploymentCommandProcessor
{
    public function __construct(
        private AddonRegistry $addonRegistry,
        private DatabaseMaintenanceService $databaseMaintenance,
    ) {}

    /**
     * @param  array{id: string, action: string, payload?: array<string, mixed>|null}  $command
     * @return array{status: string, result: string}
     */
    public function process(array $command): array
    {
        $action = (string) ($command['action'] ?? '');
        $payload = is_array($command['payload'] ?? null) ? $command['payload'] : [];

        try {
            return match ($action) {
                'set_plan' => $this->setPlan($payload),
                'clear_cache' => $this->clearCache(),
                'addon_install' => $this->addonInstall((string) ($payload['slug'] ?? '')),
                'addon_enable' => $this->addonEnable((string) ($payload['slug'] ?? '')),
                'addon_disable' => $this->addonDisable((string) ($payload['slug'] ?? '')),
                'database_backup' => $this->databaseBackup(),
                'database_migrate' => $this->databaseMigrate(),
                'database_restore' => $this->databaseRestore($payload),
                'force_sync' => ['status' => 'completed', 'result' => 'Sync acknowledged.'],
                default => ['status' => 'failed', 'result' => "Unknown command: {$action}"],
            };
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'result' => $e->getMessage()];
        }
    }

    /**
     * @param  list<array{id: string, action: string, payload?: array<string, mixed>|null}>  $commands
     * @return list<array{id: string, status: string, result: string}>
     */
    public function processMany(array $commands): array
    {
        $results = [];

        foreach ($commands as $command) {
            $outcome = $this->process($command);
            $results[] = [
                'id' => (string) ($command['id'] ?? ''),
                'status' => $outcome['status'],
                'result' => $outcome['result'],
            ];
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, result: string}
     */
    private function setPlan(array $payload): array
    {
        $settings = PlatformSetting::current();
        $settings->update([
            'plan_tier' => $payload['plan_tier'] ?? $settings->plan_tier,
            'max_seats_override' => $payload['max_seats_override'] ?? null,
            'max_halls_override' => $payload['max_halls_override'] ?? null,
            'max_branches_override' => $payload['max_branches_override'] ?? null,
        ]);

        return ['status' => 'completed', 'result' => 'Plan settings updated.'];
    }

    /**
     * @return array{status: string, result: string}
     */
    private function clearCache(): array
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return ['status' => 'completed', 'result' => 'Application cache cleared.'];
    }

    /**
     * @return array{status: string, result: string}
     */
    private function addonInstall(string $slug): array
    {
        if ($slug === '') {
            return ['status' => 'failed', 'result' => 'Addon slug is required.'];
        }

        $this->addonRegistry->install($slug);

        return ['status' => 'completed', 'result' => "Addon [{$slug}] installed."];
    }

    /**
     * @return array{status: string, result: string}
     */
    private function addonEnable(string $slug): array
    {
        if ($slug === '') {
            return ['status' => 'failed', 'result' => 'Addon slug is required.'];
        }

        $this->addonRegistry->enable($slug);

        return ['status' => 'completed', 'result' => "Addon [{$slug}] enabled."];
    }

    /**
     * @return array{status: string, result: string}
     */
    private function addonDisable(string $slug): array
    {
        if ($slug === '') {
            return ['status' => 'failed', 'result' => 'Addon slug is required.'];
        }

        $this->addonRegistry->disable($slug);

        return ['status' => 'completed', 'result' => "Addon [{$slug}] disabled."];
    }

    /**
     * @return array{status: string, result: string}
     */
    private function databaseBackup(): array
    {
        $backup = $this->databaseMaintenance->createBackup();

        return ['status' => 'completed', 'result' => 'Created backup '.$backup['filename'].'.'];
    }

    /**
     * @return array{status: string, result: string}
     */
    private function databaseMigrate(): array
    {
        $result = $this->databaseMaintenance->runMigrations();

        return ['status' => 'completed', 'result' => $result['output'] ?: 'Migrations completed.'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, result: string}
     */
    private function databaseRestore(array $payload): array
    {
        if (($payload['confirmation'] ?? '') !== 'RESTORE') {
            return ['status' => 'failed', 'result' => 'Restore confirmation missing.'];
        }

        $filename = (string) ($payload['filename'] ?? '');
        $this->databaseMaintenance->restoreBackup($filename);

        return ['status' => 'completed', 'result' => "Database restored from {$filename}."];
    }
}
