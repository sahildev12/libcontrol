<?php

namespace App\Services\Addons;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class AddonPackageInstaller
{
    /**
     * @var list<string>
     */
    private const ALLOWED_PATH_PREFIXES = [
        'app/Addons/',
        'database/migrations/addons/',
        'resources/views/',
    ];

    public function manifestsDirectory(): string
    {
        return storage_path('app/addons/manifests');
    }

    public function installUploadedPackage(UploadedFile $package): string
    {
        $tempDirectory = $this->extractPackage($package);

        try {
            [$packageRoot, $manifest] = $this->readManifest($tempDirectory);
            $slug = (string) ($manifest['slug'] ?? '');

            if ($slug === '') {
                throw new RuntimeException('Addon manifest must include a slug.');
            }

            $this->copyPackageFiles($packageRoot, $manifest);
            $this->storeManifest($slug, $manifest);

            return $slug;
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }

    private function extractPackage(UploadedFile $package): string
    {
        $tempDirectory = storage_path('app/addons/tmp/'.uniqid('upload-', true));
        File::ensureDirectoryExists($tempDirectory);

        $zip = new ZipArchive;
        $opened = $zip->open($package->getRealPath() ?: $package->getPathname());

        if ($opened !== true) {
            throw new RuntimeException('Could not open the addon ZIP file.');
        }

        if (! $zip->extractTo($tempDirectory)) {
            $zip->close();
            throw new RuntimeException('Could not extract the addon ZIP file.');
        }

        $zip->close();

        return $tempDirectory;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function readManifest(string $directory): array
    {
        $packageRoot = $this->resolvePackageRoot($directory);
        $manifestPath = $packageRoot.DIRECTORY_SEPARATOR.'addon.json';

        if (! File::exists($manifestPath)) {
            throw new RuntimeException('addon.json was not found at the root of the ZIP file.');
        }

        $manifest = json_decode((string) File::get($manifestPath), true);

        if (! is_array($manifest)) {
            throw new RuntimeException('addon.json is not valid JSON.');
        }

        foreach (['slug', 'name', 'version', 'provider'] as $field) {
            if (! is_string($manifest[$field] ?? null) || trim((string) $manifest[$field]) === '') {
                throw new RuntimeException("addon.json is missing [{$field}].");
            }
        }

        $provider = (string) $manifest['provider'];
        if (! str_starts_with($provider, 'App\\Addons\\')) {
            throw new RuntimeException('Addon provider must live under the App\\Addons namespace.');
        }

        $manifest['slug'] = strtolower(trim((string) $manifest['slug']));

        if (! preg_match('/^[a-z0-9][a-z0-9_-]*$/', $manifest['slug'])) {
            throw new RuntimeException('Addon slug may only contain letters, numbers, underscores, and hyphens.');
        }

        return [$packageRoot, $manifest];
    }

    private function resolvePackageRoot(string $directory): string
    {
        $manifestPath = $directory.DIRECTORY_SEPARATOR.'addon.json';

        if (File::exists($manifestPath)) {
            return $directory;
        }

        $children = collect(File::directories($directory))
            ->filter(fn (string $path) => basename($path) !== '__MACOSX')
            ->values();

        if ($children->count() === 1 && File::exists($children->first().DIRECTORY_SEPARATOR.'addon.json')) {
            return $children->first();
        }

        return $directory;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function copyPackageFiles(string $directory, array $manifest): void
    {
        $root = $directory;
        $entries = File::allFiles($root);
        $copiedAny = false;

        foreach ($entries as $file) {
            $relativePath = str_replace('\\', '/', ltrim(str_replace($root, '', $file->getPathname()), '/\\'));

            if ($relativePath === 'addon.json') {
                continue;
            }

            if (! $this->isAllowedPackagePath($relativePath)) {
                throw new RuntimeException("Addon file [{$relativePath}] is not in an allowed folder.");
            }

            $target = base_path($relativePath);
            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
            $copiedAny = true;
        }

        if (! $copiedAny) {
            throw new RuntimeException('Addon ZIP did not contain any installable files.');
        }

        $provider = (string) ($manifest['provider'] ?? '');
        if ($provider !== '' && ! class_exists($provider)) {
            throw new RuntimeException("Addon provider [{$provider}] could not be loaded after install.");
        }
    }

    private function isAllowedPackagePath(string $relativePath): bool
    {
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return false;
        }

        foreach (self::ALLOWED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function storeManifest(string $slug, array $manifest): void
    {
        File::ensureDirectoryExists($this->manifestsDirectory());
        File::put(
            $this->manifestsDirectory().DIRECTORY_SEPARATOR.$slug.'.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
