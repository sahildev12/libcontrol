<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PlatformBrandService
{
    /**
     * @return array{display_name: string, favicon_url: string|null, simple_logo_url: string|null, logo_with_text_url: string|null}
     */
    public function branding(): array
    {
        $settings = PlatformSetting::current();

        return [
            'display_name' => $settings->displayName(),
            'favicon_url' => $settings->faviconUrl(),
            'simple_logo_url' => $settings->simpleLogoUrl(),
            'logo_with_text_url' => $settings->logoWithTextUrl(),
        ];
    }

    public function storeUpload(?UploadedFile $file, string $type): ?string
    {
        if (! $file) {
            return null;
        }

        $settings = PlatformSetting::current();
        $directory = 'platform/brand';

        $existing = match ($type) {
            'favicon' => $settings->favicon_path,
            'simple_logo' => $settings->simple_logo_path,
            'logo_with_text' => $settings->logo_with_text_path ?: $settings->logo_path,
            default => null,
        };

        if ($existing) {
            Storage::disk('public')->delete($existing);
        }

        return $file->store($directory, 'public');
    }
}
