<?php

namespace App\Services\Addons;

use App\Models\AddonInstallation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AddonRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        $catalog = config('addons', []);

        if (! is_dir($this->manifestsDirectory())) {
            return $catalog;
        }

        foreach (glob($this->manifestsDirectory().'/*.json') ?: [] as $manifestFile) {
            $manifest = json_decode((string) file_get_contents($manifestFile), true);

            if (! is_array($manifest) || ! is_string($manifest['slug'] ?? null) || $manifest['slug'] === '') {
                continue;
            }

            $catalog[$manifest['slug']] = array_merge($catalog[$manifest['slug']] ?? [], $manifest);
        }

        return $catalog;
    }

    private function manifestsDirectory(): string
    {
        return storage_path('app/addons/manifests');
    }

    public function manifest(string $slug): ?array
    {
        $catalog = $this->catalog();

        return $catalog[$slug] ?? null;
    }

    public function isInstalled(string $slug): bool
    {
        if (! $this->tableExists()) {
            return false;
        }

        return AddonInstallation::query()->where('slug', $slug)->exists();
    }

    public function isEnabled(string $slug): bool
    {
        if (! $this->tableExists()) {
            return false;
        }

        return AddonInstallation::query()
            ->where('slug', $slug)
            ->where('enabled', true)
            ->exists();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function installed(): Collection
    {
        if (! $this->tableExists()) {
            return collect();
        }

        return AddonInstallation::query()
            ->orderBy('slug')
            ->get()
            ->map(fn (AddonInstallation $row) => $this->serializeInstallation($row));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeForSettings(string $slug): array
    {
        $manifest = $this->manifest($slug);

        if (! $manifest) {
            throw new RuntimeException("Unknown addon [{$slug}].");
        }

        $installation = $this->tableExists()
            ? AddonInstallation::query()->where('slug', $slug)->first()
            : null;

        return [
            'slug' => $slug,
            'name' => (string) ($manifest['name'] ?? Str::title($slug)),
            'description' => (string) ($manifest['description'] ?? ''),
            'version' => (string) ($manifest['version'] ?? '1.0.0'),
            'installed' => $installation !== null,
            'enabled' => (bool) ($installation?->enabled ?? false),
            'installed_version' => $installation?->version,
            'installed_at' => $installation?->installed_at?->toIso8601String(),
            'settings_route' => $manifest['settings_route'] ?? null,
            'settings_url' => isset($manifest['settings_route']) && \Illuminate\Support\Facades\Route::has($manifest['settings_route'])
                ? route($manifest['settings_route'])
                : null,
        ];
    }

    public function install(string $slug): AddonInstallation
    {
        $manifest = $this->manifest($slug);

        if (! $manifest) {
            throw new RuntimeException("Unknown addon [{$slug}].");
        }

        $this->runMigrations($manifest);

        $installation = AddonInstallation::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'version' => (string) ($manifest['version'] ?? '1.0.0'),
                'enabled' => true,
                'installed_at' => now(),
            ],
        );

        $this->bootAddonProvider($manifest);

        return $installation;
    }

    public function uninstall(string $slug): void
    {
        AddonInstallation::query()->where('slug', $slug)->delete();
    }

    public function enable(string $slug): AddonInstallation
    {
        $installation = AddonInstallation::query()->where('slug', $slug)->firstOrFail();
        $installation->update(['enabled' => true]);

        $manifest = $this->manifest($slug);
        if ($manifest) {
            $this->bootAddonProvider($manifest);
        }

        return $installation->fresh();
    }

    public function disable(string $slug): AddonInstallation
    {
        $installation = AddonInstallation::query()->where('slug', $slug)->firstOrFail();
        $installation->update(['enabled' => false]);

        return $installation->fresh();
    }

    public function bootEnabledAddons(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $enabled = AddonInstallation::query()->where('enabled', true)->pluck('slug');

        foreach ($enabled as $slug) {
            $manifest = $this->manifest((string) $slug);
            if ($manifest) {
                $this->bootAddonProvider($manifest);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function bootAddonProvider(array $manifest): void
    {
        $provider = $manifest['provider'] ?? null;

        if (! is_string($provider) || ! class_exists($provider)) {
            return;
        }

        app()->register($provider);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function runMigrations(array $manifest): void
    {
        $path = $manifest['migrations_path'] ?? null;

        if (! is_string($path) || $path === '') {
            return;
        }

        $absolute = base_path($path);

        if (! is_dir($absolute)) {
            return;
        }

        $files = glob($absolute.'/*.php') ?: [];

        if ($files === []) {
            return;
        }

        if (Schema::hasTable('migrations')) {
            $pending = collect($files)->contains(function (string $file): bool {
                $name = pathinfo($file, PATHINFO_FILENAME);

                return ! DB::table('migrations')->where('migration', $name)->exists();
            });

            if (! $pending) {
                return;
            }
        }

        $batch = Schema::hasTable('migrations')
            ? (int) DB::table('migrations')->max('batch') + 1
            : 1;

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (Schema::hasTable('migrations') && DB::table('migrations')->where('migration', $name)->exists()) {
                continue;
            }

            $migration = require $file;

            if (is_object($migration) && method_exists($migration, 'up')) {
                try {
                    $migration->up();
                } catch (\Throwable $exception) {
                    if (! str_contains(strtolower($exception->getMessage()), 'already exists')) {
                        throw $exception;
                    }
                }
            }

            if (Schema::hasTable('migrations') && ! DB::table('migrations')->where('migration', $name)->exists()) {
                DB::table('migrations')->insert([
                    'migration' => $name,
                    'batch' => $batch,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInstallation(AddonInstallation $row): array
    {
        return $this->serializeForSettings($row->slug);
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('addon_installations');
        } catch (\Throwable) {
            return false;
        }
    }
}
