<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBranchSettingsRequest;
use App\Http\Requests\UpdatePlatformPlanRequest;
use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Services\Addons\AddonRegistry;
use App\Services\BranchBrandService;
use App\Services\DatabaseMaintenanceService;
use App\Services\LibraryWebsiteService;
use App\Services\MailDeliveryService;
use App\Services\PlatformBrandService;
use App\Services\PlanLimitService;
use App\Services\Settings\SettingsPayloadService;
use App\Support\InstallState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private PlanLimitService $planLimitService,
        private SettingsPayloadService $settingsPayload,
    ) {}

    public function index(Request $request, BranchBrandService $branchBrandService, AddonRegistry $addonRegistry, DatabaseMaintenanceService $databaseMaintenance, LibraryWebsiteService $libraryWebsiteService, MailDeliveryService $mailDelivery): View
    {
        $branch = $this->optionalActiveBranch($request);
        $viewingAll = $this->viewingAllBranches($request);

        $user = $request->user();
        $isHub = InstallState::isHub();

        if ($user?->isDeveloperAdmin() && ! $isHub) {
            abort(403, 'Developer settings are managed from the LibControl hub.');
        }

        abort_unless(
            $branch || $user?->isClientAdmin() || ($user?->isDeveloperAdmin() && $isHub),
            403,
        );

        $settings = $branch ? $this->settingsPayload->serializeBranchSettings($branch) : null;
        $platformSettings = PlatformSetting::current();
        $isPlatformAdmin = (bool) $user?->isClientAdmin();
        $isClientAdmin = (bool) $user?->isClientAdmin();
        $isDeveloperAdmin = (bool) $user?->isDeveloperAdmin();
        $planSnapshot = $this->planLimitService->snapshot();
        $licenseServerEnabled = (bool) config('libcontrol.license_server.enabled');
        $deploymentsUrl = $licenseServerEnabled && Route::has('developer.deployments.index')
            ? route('developer.deployments.index')
            : null;

        $availableAddons = $isDeveloperAdmin
            ? $addonRegistry->catalogForSettings()
            : [];
        $publicUrl = trim((string) config('libcontrol.deployment.public_url', ''));
        $appUrl = rtrim((string) config('app.url'), '/');
        $deploymentInfo = [
            'public_url' => $publicUrl !== '' ? $publicUrl : $appUrl,
            'app_url' => $appUrl,
            'library_code' => $platformSettings->library_code,
            'sync_endpoint' => config('libcontrol.deployment.sync_endpoint'),
            'public_url_is_localhost' => $this->isLocalhostUrl($publicUrl !== '' ? $publicUrl : $appUrl),
        ];

        $websiteSettings = $libraryWebsiteService->settingsPayload($platformSettings);
        $emailNotificationSettings = $this->settingsPayload->serializeEmailNotificationSettings($platformSettings);
        $mailDeliveryStatus = $mailDelivery->status();
        $globalExpiryReminderDays = $viewingAll
            ? (int) (Branch::query()->value('expiry_reminder_days') ?: config('libcontrol.defaults.expiry_reminder_days', 10))
            : null;

        return view('settings.index', compact('branch', 'settings', 'platformSettings', 'isPlatformAdmin', 'isClientAdmin', 'isDeveloperAdmin', 'isHub', 'planSnapshot', 'viewingAll', 'licenseServerEnabled', 'deploymentsUrl', 'availableAddons', 'databaseMaintenance', 'deploymentInfo', 'websiteSettings', 'emailNotificationSettings', 'mailDeliveryStatus', 'globalExpiryReminderDays') + [
            'portalContext' => false,
            'managedTenant' => null,
            'portalBranches' => [],
            'portalBranchId' => null,
            'portalRoutes' => [],
        ]);
    }

    public function updateGlobal(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isClientAdmin(), 403);
        abort_unless($this->viewingAllBranches($request), 422, 'Switch to all branches to update library-wide settings.');

        $validated = $request->validate([
            'expiry_reminder_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        Branch::query()->update([
            'expiry_reminder_days' => $validated['expiry_reminder_days'],
        ]);

        return response()->json([
            'message' => 'Plan expiry reminder updated for all branches.',
            'expiry_reminder_days' => $validated['expiry_reminder_days'],
        ]);
    }

    public function updateEmailNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_welcome_enabled' => ['nullable', 'boolean'],
            'email_birthday_enabled' => ['nullable', 'boolean'],
            'email_offers_enabled' => ['nullable', 'boolean'],
            'email_recovery_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = PlatformSetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Email notification settings saved.',
            'email_notifications' => $this->settingsPayload->serializeEmailNotificationSettings($settings->fresh()),
        ]);
    }

    public function syncRuntime(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isDeveloperAdmin(), 403);

        Artisan::call('app:sync-runtime-metrics');

        $publicUrl = trim((string) config('libcontrol.deployment.public_url', ''));
        $appUrl = $publicUrl !== '' ? $publicUrl : rtrim((string) config('app.url'), '/');
        $code = PlatformSetting::current()->library_code;

        return response()->json([
            'message' => "Library {$code} synced to Phenomit with student app URL {$appUrl}.",
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isDeveloperAdmin(), 403);

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return response()->json([
            'message' => 'Application cache cleared.',
        ]);
    }

    public function update(UpdateBranchSettingsRequest $request, BranchBrandService $branchBrandService): JsonResponse
    {
        $branch = $this->optionalActiveBranch($request);
        abort_unless($branch, 422, 'Select a specific branch to update library hours and branding.');

        $data = $request->safe()->except(['logo_with_text', 'simple_logo', 'favicon']);

        $branch->update($data);

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => $this->settingsPayload->serializeBranchSettings($branch->fresh()),
        ]);
    }

    public function updatePlatform(UpdatePlatformSettingsRequest $request, PlatformBrandService $platformBrand): JsonResponse
    {
        $settings = PlatformSetting::current();
        $data = $request->safe()->except(['id_card_logo']);

        if ($request->hasFile('id_card_logo')) {
            $data['id_card_logo_path'] = $platformBrand->storeUpload($request->file('id_card_logo'), 'id_card_logo');
        }

        $settings->update($data);

        return response()->json([
            'message' => 'Global settings saved.',
            'platform_settings' => $this->settingsPayload->serializePlatformSettings($settings->fresh()),
        ]);
    }

    public function updatePlatformPlan(UpdatePlatformPlanRequest $request): JsonResponse
    {
        $settings = PlatformSetting::current();
        $settings->update($request->validated());

        return response()->json([
            'message' => 'Plan settings saved.',
            'plan' => $this->planLimitService->snapshot(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function isLocalhostUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

}
