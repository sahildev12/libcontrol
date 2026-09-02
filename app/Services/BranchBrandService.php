<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PlatformSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BranchBrandService
{
    public function displayName(Branch $branch): string
    {
        return $branch->display_name ?: $branch->name;
    }

    public function assetUrl(Branch $branch, ?string $path, ?string $defaultPublicPath = null): ?string
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        if ($path && Storage::disk('public')->exists($path)) {
            return $this->publicStorageUrl($path);
        }

        if ($path && file_exists(storage_path('app/public/'.$path))) {
            return $this->publicStorageUrl($path);
        }

        if ($defaultPublicPath && file_exists(public_path($defaultPublicPath))) {
            return asset($defaultPublicPath);
        }

        return null;
    }

    public function faviconUrl(Branch $branch): ?string
    {
        return PlatformSetting::current()->faviconUrl();
    }

    public function simpleLogoUrl(Branch $branch): ?string
    {
        return PlatformSetting::current()->simpleLogoUrl();
    }

    public function logoWithTextUrl(Branch $branch): ?string
    {
        return PlatformSetting::current()->logoWithTextUrl();
    }

    public function storeUpload(Branch $branch, UploadedFile $file, string $type): string
    {
        $directory = "branches/{$branch->id}/brand";

        $existing = match ($type) {
            'logo_with_text' => $branch->logo_with_text_path,
            'simple_logo' => $branch->simple_logo_path,
            'favicon' => $branch->favicon_path,
            default => null,
        };

        if ($existing) {
            Storage::disk('public')->delete($existing);
        }

        return $file->store($directory, 'public');
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
