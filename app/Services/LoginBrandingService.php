<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LoginBrandingService
{
    public const PORTAL_ADMIN = 'admin';

    public const PORTAL_BRANCH = 'branch';

    /**
     * @return array{portal: string, title: string, subtitle: string, name: string, logo_url: string|null, favicon_url: string|null}
     */
    public function forPortal(string $portal, ?Request $request = null): array
    {
        if ($portal === self::PORTAL_ADMIN) {
            return [
                'portal' => self::PORTAL_ADMIN,
                'title' => 'Admin login',
                'subtitle' => 'For the people who manage the whole system',
                'name' => config('app.name', 'LibSpace'),
                'logo_url' => PlatformSetting::current()->logoUrl(),
                'favicon_url' => PlatformSetting::current()->faviconUrl(),
            ];
        }

        $branch = $this->resolveBranch($request);
        $settings = PlatformSetting::current();

        return [
            'portal' => self::PORTAL_BRANCH,
            'title' => 'Branch login',
            'subtitle' => $branch ? 'Staff access for this library' : 'Sign in with your branch account',
            'name' => $branch ? app(BranchBrandService::class)->displayName($branch) : $settings->displayName(),
            'logo_url' => $branch
                ? (app(BranchBrandService::class)->logoWithTextUrl($branch)
                    ?: app(BranchBrandService::class)->simpleLogoUrl($branch))
                : $settings->logoUrl(),
            'favicon_url' => $branch
                ? app(BranchBrandService::class)->faviconUrl($branch)
                : $settings->faviconUrl(),
        ];
    }

    public function storePlatformLogo(?\Illuminate\Http\UploadedFile $file, string $type): ?string
    {
        if (! $file) {
            return null;
        }

        $directory = 'platform/brand';
        $settings = PlatformSetting::current();
        $existing = $type === 'favicon' ? $settings->favicon_path : $settings->logo_path;

        if ($existing) {
            Storage::disk('public')->delete($existing);
        }

        return $file->store($directory, 'public');
    }

    public function isAdminUser(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    private function resolveBranch(?Request $request): ?Branch
    {
        $branchId = $request?->query('branch');

        if ($branchId && ctype_digit((string) $branchId)) {
            $requested = Branch::query()->find((int) $branchId);

            if ($requested) {
                return $requested;
            }
        }

        $withLogo = Branch::query()
            ->where(function ($query) {
                $query->whereNotNull('logo_with_text_path')
                    ->orWhereNotNull('simple_logo_path');
            })
            ->orderBy('id')
            ->first();

        if ($withLogo) {
            return $withLogo;
        }

        return Branch::query()->orderBy('id')->first();
    }
}
