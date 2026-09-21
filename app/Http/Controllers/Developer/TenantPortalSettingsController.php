<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\DatabaseMaintenanceService;
use App\Services\Developer\TenantPortalSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPortalSettingsController extends Controller
{
    public function __construct(
        private TenantPortalSettingsService $portalSettings,
    ) {}

    public function index(): View
    {
        $tenants = Tenant::query()
            ->where('active', true)
            ->orderBy('client_name')
            ->get();

        return view('developer.portals.index', compact('tenants'));
    }

    public function show(Request $request, Tenant $tenant, DatabaseMaintenanceService $databaseMaintenance): View
    {
        $data = $this->portalSettings->settingsViewData($tenant, $request);
        $data['databaseMaintenance'] = $databaseMaintenance;

        return view('settings.index', $data);
    }

    public function switchBranch(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->portalSettings->switchBranch($request, $tenant);

        return redirect()
            ->route('developer.portals.settings', $tenant)
            ->with('status', 'Branch scope updated.');
    }

    public function updateBranch(Request $request, Tenant $tenant): JsonResponse
    {
        return response()->json(
            $this->portalSettings->updateBranchSettings($tenant, $request, $request->user()),
        );
    }

    public function updatePlatform(Request $request, Tenant $tenant): JsonResponse
    {
        return response()->json(
            $this->portalSettings->updatePlatformSettings($tenant, $request, $request->user()),
        );
    }

    public function updateWebsite(Request $request, Tenant $tenant): JsonResponse
    {
        return response()->json(
            $this->portalSettings->updateWebsiteSettings($tenant, $request, $request->user()),
        );
    }

    public function updateEmailNotifications(Request $request, Tenant $tenant): JsonResponse
    {
        return response()->json(
            $this->portalSettings->updateEmailNotifications($tenant, $request, $request->user()),
        );
    }

    public function updateGlobal(Request $request, Tenant $tenant): JsonResponse
    {
        return response()->json(
            $this->portalSettings->updateGlobalSettings($tenant, $request, $request->user()),
        );
    }
}
