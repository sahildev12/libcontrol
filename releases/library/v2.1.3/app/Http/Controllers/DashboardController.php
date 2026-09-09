<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): View
    {
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

            return view('dashboard', [
                'mode' => 'admin',
                'scopeLabel' => $scopeLabel,
                'admin' => $admin,
                'branch' => null,
            ]);
        }

        $branch = $dashboardService->branchOverview($branchId);

        return view('dashboard', [
            'mode' => 'branch',
            'scopeLabel' => $scopeLabel !== '' ? $scopeLabel : 'Branch',
            'admin' => null,
            'branch' => $branch,
        ]);
    }
}
