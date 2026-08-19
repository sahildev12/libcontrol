<?php

namespace App\View\Components;

use App\Services\BranchBrandService;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $branding;

    public function __construct(BranchBrandService $branchBrandService, BranchContext $branchContext)
    {
        $user = auth()->user();
        $branch = null;

        if ($user) {
            if ($user->branch_id) {
                $branch = $user->branch;
            } elseif ($user->isPlatformAdmin()) {
                try {
                    $branch = $branchContext->branch($user, request());
                } catch (\Throwable) {
                    $branch = null;
                }
            }
        }

        $this->branding = $branch
            ? [
                'display_name' => $branchBrandService->displayName($branch),
                'favicon_url' => $branchBrandService->faviconUrl($branch),
                'simple_logo_url' => $branchBrandService->simpleLogoUrl($branch),
                'logo_with_text_url' => $branchBrandService->logoWithTextUrl($branch),
            ]
            : [
                'display_name' => config('app.name'),
                'favicon_url' => null,
                'simple_logo_url' => null,
                'logo_with_text_url' => null,
            ];
    }

    public function render(): View
    {
        return view('layouts.admin');
    }
}
