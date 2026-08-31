<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\FeeInstallment;
use App\Models\Hall;
use App\Models\PlatformSetting;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Observers\RecordsModelChanges;
use App\Services\BranchContext;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Support\Runtime\DeploymentState::class);
        $this->app->singleton(\App\Support\Runtime\SyncCoordinator::class);
    }

    public function boot(): void
    {
        $auditor = new RecordsModelChanges;
        Branch::observe($auditor);
        Hall::observe($auditor);
        Student::observe($auditor);
        Enquiry::observe($auditor);
        SeatBooking::observe($auditor);
        PlatformSetting::observe($auditor);
        User::observe($auditor);
        FeeInstallment::observe($auditor);

        Broadcast::routes(['middleware' => ['web', 'auth', 'branch']]);

        View::composer(['layouts.partials.admin-topbar', 'layouts.partials.admin-sidebar'], function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $branchContext = app(BranchContext::class);
            $viewingAll = $branchContext->viewingAll($user, request());
            $activeBranch = null;
            $branchId = null;

            try {
                if ($user->branch_id || $user->isPlatformAdmin()) {
                    $activeBranch = $viewingAll ? null : $branchContext->optionalBranch($user, request());
                    $branchId = $viewingAll ? null : $branchContext->optionalBranchId($user, request());
                }
            } catch (\Throwable) {
                $activeBranch = null;
            }

            $recentAlerts = collect();
            $alertCount = 0;

            if ($user->branch_id || $user->isPlatformAdmin()) {
                $notificationService = app(NotificationService::class);
                $recentAlerts = $notificationService->alertsForBranch($branchId, $user, 8);
                $alertCount = $notificationService->unreadCount($branchId, $user);
            }

            $view->with([
                'activeBranch' => $activeBranch,
                'viewingAllBranches' => $viewingAll,
                'allBranches' => $user->isPlatformAdmin()
                    ? Branch::query()->orderBy('name')->get(['id', 'name'])
                    : collect(),
                'isPlatformAdmin' => $user->isPlatformAdmin(),
                'isDeveloperAdmin' => $user->isDeveloperAdmin(),
                'licenseServerEnabled' => (bool) config('libspace.license_server.enabled'),
                'adminTypeLabel' => $user->adminTypeLabel(),
                'recentAlerts' => $recentAlerts,
                'alertCount' => $alertCount,
            ]);
        });
    }
}
