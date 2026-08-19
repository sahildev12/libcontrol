<?php

namespace App\Providers;

use App\Models\Branch;
use App\Services\BranchContext;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web', 'auth', 'branch']]);

        View::composer(['layouts.partials.admin-topbar', 'layouts.partials.admin-sidebar'], function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $branchContext = app(BranchContext::class);
            $activeBranch = null;
            $branchId = null;

            try {
                if ($user->branch_id || $user->isPlatformAdmin()) {
                    $activeBranch = $branchContext->branch($user, request());
                    $branchId = $activeBranch->id;
                }
            } catch (\Throwable) {
                $activeBranch = null;
            }

            $recentAlerts = collect();
            $alertCount = 0;

            if ($branchId) {
                $recentAlerts = app(NotificationService::class)->alertsForBranch($branchId, 8);
                $alertCount = $recentAlerts->count();
            }

            $view->with([
                'activeBranch' => $activeBranch,
                'allBranches' => $user->isPlatformAdmin()
                    ? Branch::query()->orderBy('name')->get(['id', 'name'])
                    : collect(),
                'isPlatformAdmin' => $user->isPlatformAdmin(),
                'adminTypeLabel' => $user->adminTypeLabel(),
                'recentAlerts' => $recentAlerts,
                'alertCount' => $alertCount,
            ]);
        });
    }
}
