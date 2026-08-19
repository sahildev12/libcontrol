<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService, FeeService $feeService): View
    {
        $branchId = $this->activeBranchId($request);
        $stats = $dashboardService->statsForBranch($branchId);
        $feeOverview = $feeService->overviewForBranch($branchId);

        return view('dashboard', compact('stats', 'feeOverview'));
    }
}
