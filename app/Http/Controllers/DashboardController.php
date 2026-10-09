<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\DashboardService;
use App\Services\Growth\LibraryCombinedGrowthScoreService;
use App\Services\Profile\LibraryProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardService $dashboardService,
        LibraryCombinedGrowthScoreService $combinedGrowthScore,
        LibraryProfileCompletionService $profileCompletion,
    ): View|RedirectResponse {
        if ($request->user()?->isDeveloperAdmin()) {
            return redirect()->route(
                \Illuminate\Support\Facades\Route::has('developer.deployments.index')
                    ? 'developer.deployments.index'
                    : 'settings.index',
                \Illuminate\Support\Facades\Route::has('developer.deployments.index') ? [] : ['tab' => 'developer'],
            );
        }

        $branchId = $this->optionalActiveBranchId($request);
        $viewingAll = $this->viewingAllBranches($request);
        $isAdminOverview = (bool) $request->user()?->isPlatformAdmin() && $viewingAll;
        $scopeLabel = $viewingAll
            ? 'All branches'
            : ($this->optionalActiveBranch($request)?->name ?? '');

        if ($isAdminOverview) {
            $from = $request->filled('from')
                ? Carbon::parse($request->input('from'))->startOfDay()
                : Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->startOfMonth();
            $to = $request->filled('to')
                ? Carbon::parse($request->input('to'))->endOfDay()
                : Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            $revenueMonths = (int) $request->input('revenue_months', 6);
            if (! in_array($revenueMonths, [3, 6, 12], true)) {
                $revenueMonths = 6;
            }

            $admin = $dashboardService->adminOverview($from, $to, $revenueMonths);

            $growthScore = null;
            $businessPerformance = null;
            $profileCompletionScore = null;
            $growthActions = [];
            $growthBranch = null;
            $combinedGrowth = null;

            if (config('growth.enabled', true)) {
                $growthBranch = $this->resolveGrowthBranchForDashboard($request);
                if ($growthBranch) {
                    $combinedGrowth = $combinedGrowthScore->forBranch($growthBranch, $request->user());
                    $growthScore = $combinedGrowth['action_progress'];
                    $businessPerformance = $combinedGrowth['business_performance'];
                    $growthActions = config('growth.actions', []);
                }
            }

            $profileCompletionScore = $profileCompletion->scoreForLibrary($request->user());

            return view('dashboard', [
                'mode' => 'admin',
                'scopeLabel' => $scopeLabel,
                'admin' => $admin,
                'branch' => null,
                'growthScore' => $growthScore,
                'businessPerformance' => $businessPerformance,
                'profileCompletionScore' => $profileCompletionScore,
                'combinedGrowth' => $combinedGrowth,
                'growthActions' => $growthActions,
                'growthBranch' => $growthBranch,
            ]);
        }

        $branch = $dashboardService->branchOverview($branchId);

        return view('dashboard', [
            'mode' => 'branch',
            'scopeLabel' => $scopeLabel !== '' ? $scopeLabel : 'Branch',
            'admin' => null,
            'branch' => $branch,
            'growthScore' => null,
            'businessPerformance' => null,
            'profileCompletionScore' => null,
            'combinedGrowth' => null,
            'growthActions' => [],
            'growthBranch' => null,
        ]);
    }

    private function resolveGrowthBranchForDashboard(Request $request): ?Branch
    {
        $branch = $this->optionalActiveBranch($request);
        if ($branch) {
            return $branch;
        }

        if ($request->user()?->branch_id) {
            return Branch::query()->find((int) $request->user()->branch_id);
        }

        return Branch::query()->orderBy('name')->first();
    }
}
