<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\Request;

class LoginBrandingService
{
    public const PORTAL_ADMIN = 'admin';

    public const PORTAL_BRANCH = 'branch';

    public function __construct(
        private BranchBrandService $branchBrandService,
    ) {}

    /**
     * @return array{portal: string, title: string, subtitle: string, name: string, logo_url: string|null, favicon_url: string|null}
     */
    public function forPasswordReset(): array
    {
        $settings = PlatformSetting::current();

        return [
            'portal' => 'auth',
            'title' => 'Forgot password',
            'subtitle' => config('libcontrol.product.byline'),
            'name' => $settings->displayName(),
            'logo_url' => $settings->logoUrl(),
            'favicon_url' => $settings->faviconUrl(),
        ];
    }

    /**
     * @return array{portal: string, title: string, subtitle: string, name: string, logo_url: string|null, favicon_url: string|null}
     */
    public function forPortal(string $portal, ?Request $request = null): array
    {
        $settings = PlatformSetting::current();

        if ($portal === self::PORTAL_ADMIN) {
            return [
                'portal' => self::PORTAL_ADMIN,
                'title' => 'Admin login',
                'subtitle' => 'For the people who manage the whole system',
                'name' => $settings->displayName(),
                'logo_url' => $settings->logoUrl(),
                'favicon_url' => $settings->faviconUrl(),
            ];
        }

        $branch = $this->resolveBranch($request);

        return [
            'portal' => self::PORTAL_BRANCH,
            'title' => 'Branch login',
            'subtitle' => $branch ? 'Staff access for this library' : 'Sign in with your branch account',
            'name' => $branch ? $this->branchBrandService->displayName($branch) : $settings->displayName(),
            'logo_url' => $settings->logoUrl(),
            'favicon_url' => $settings->faviconUrl(),
        ];
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

        return Branch::query()->orderBy('id')->first();
    }
}
