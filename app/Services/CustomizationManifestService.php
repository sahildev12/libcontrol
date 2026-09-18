<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Services\Addons\AddonRegistry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CustomizationManifestService
{
    private const STORAGE_PATH = 'customization.json';

    /**
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        $base = config('libcontrol-customization', []);
        $overrides = $this->readOverrides();

        return [
            'profile' => array_merge(
                $base['profile'] ?? [],
                $overrides['profile'] ?? [],
            ),
            'library_styles' => $base['library_styles'] ?? [],
            'features' => $this->mergeFeatures($base['features'] ?? [], $overrides['features'] ?? []),
            'committed_changes' => array_values($base['changes'] ?? []),
            'changes' => array_values($overrides['changes'] ?? []),
            'pending' => array_values($overrides['pending'] ?? []),
            'runtime' => $this->runtimeSnapshot(),
            'sources' => [
                'config' => 'config/libcontrol-customization.php',
                'overrides' => 'storage/app/'.self::STORAGE_PATH,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveOverrides(array $payload): void
    {
        $current = $this->readOverrides();

        $next = [
            'profile' => array_merge($current['profile'] ?? [], [
                'notes' => $payload['notes'] ?? ($current['profile']['notes'] ?? null),
            ]),
            'pending' => array_values($payload['pending'] ?? $current['pending'] ?? []),
            'changes' => array_values($payload['changes'] ?? $current['changes'] ?? []),
            'updated_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put(self::STORAGE_PATH, json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function readOverrides(): array
    {
        if (! Storage::disk('local')->exists(self::STORAGE_PATH)) {
            return [];
        }

        $raw = Storage::disk('local')->get(self::STORAGE_PATH);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $base
     * @param  array<string, array<string, mixed>>  $overrides
     * @return list<array<string, mixed>>
     */
    private function mergeFeatures(array $base, array $overrides): array
    {
        $merged = [];

        foreach ($base as $key => $feature) {
            $merged[] = [
                'key' => $key,
                ...$feature,
                ...($overrides[$key] ?? []),
            ];
        }

        foreach ($overrides as $key => $feature) {
            if (array_key_exists($key, $base)) {
                continue;
            }

            $merged[] = [
                'key' => $key,
                ...$feature,
            ];
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    private function runtimeSnapshot(): array
    {
        $settings = PlatformSetting::current();
        $addons = app(AddonRegistry::class)->installed();

        return [
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'public_url' => config('libcontrol.deployment.public_url'),
            'library_code' => $settings->library_code,
            'plan_tier' => $settings->planTier(),
            'enquiries_enabled' => (bool) config('libcontrol.modules.enquiries'),
            'installed_addons' => $addons->pluck('slug')->values()->all(),
            'id_card_template' => $settings->idCardTemplate(),
            'git_branch' => $this->detectGitBranch(),
        ];
    }

    private function detectGitBranch(): ?string
    {
        $head = base_path('.git/HEAD');

        if (! File::exists($head)) {
            return null;
        }

        $line = trim((string) File::get($head));

        if (str_starts_with($line, 'ref: ')) {
            return basename(str_replace('ref: ', '', $line));
        }

        return substr($line, 0, 8);
    }
}
