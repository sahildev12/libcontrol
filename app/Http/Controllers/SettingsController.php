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
use App\Services\LibraryScheduleService;
use App\Services\LibraryWebsiteService;
use App\Services\MailDeliveryService;
use App\Services\PlatformBrandService;
use App\Services\PlanLimitService;
use App\Services\StudentCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private StudentCodeService $studentCodeService,
        private PlanLimitService $planLimitService,
    ) {}

    public function index(Request $request, BranchBrandService $branchBrandService, AddonRegistry $addonRegistry, DatabaseMaintenanceService $databaseMaintenance, LibraryWebsiteService $libraryWebsiteService, MailDeliveryService $mailDelivery): View
    {
        $branch = $this->optionalActiveBranch($request);
        $viewingAll = $this->viewingAllBranches($request);

        abort_unless($branch || $request->user()?->isPlatformAdmin(), 403);

        $settings = $branch ? $this->serializeSettings($branch, $branchBrandService) : null;
        $platformSettings = PlatformSetting::current();
        $isPlatformAdmin = (bool) $request->user()?->isPlatformAdmin();
        $isDeveloperAdmin = (bool) $request->user()?->isDeveloperAdmin();
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
        $emailNotificationSettings = $this->serializeEmailNotificationSettings($platformSettings);
        $mailDeliveryStatus = $mailDelivery->status();

        return view('settings.index', compact('branch', 'settings', 'platformSettings', 'isPlatformAdmin', 'isDeveloperAdmin', 'planSnapshot', 'viewingAll', 'licenseServerEnabled', 'deploymentsUrl', 'availableAddons', 'databaseMaintenance', 'deploymentInfo', 'websiteSettings', 'emailNotificationSettings', 'mailDeliveryStatus'));
    }

    public function updateEmailNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_welcome_enabled' => ['nullable', 'boolean'],
            'email_birthday_enabled' => ['nullable', 'boolean'],
            'email_offers_enabled' => ['nullable', 'boolean'],
            'email_marketing_enabled' => ['nullable', 'boolean'],
            'email_recovery_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = PlatformSetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Email notification settings saved.',
            'email_notifications' => $this->serializeEmailNotificationSettings($settings->fresh()),
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
            'settings' => $this->serializeSettings($branch->fresh(), $branchBrandService),
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
            'platform_settings' => $this->serializePlatformSettings($settings->fresh()),
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
    private function serializeSettings(Branch $branch, BranchBrandService $branchBrandService): array
    {
        return [
            'display_name' => $branch->display_name,
            'expiry_reminder_days' => $branch->expiry_reminder_days ?: config('libcontrol.defaults.expiry_reminder_days'),
            'library_open_time' => $branch->library_open_time ? substr((string) $branch->library_open_time, 0, 5) : '09:00',
            'library_close_time' => $branch->library_close_time ? substr((string) $branch->library_close_time, 0, 5) : '18:00',
            'is_open_24_hours' => (bool) $branch->is_open_24_hours,
            'require_student_contact' => (bool) $branch->require_student_contact,
            'time_slot_options' => LibraryScheduleService::forBranch($branch)->timeSlotOptions(),
            'logo_with_text_url' => $branchBrandService->logoWithTextUrl($branch),
            'simple_logo_url' => $branchBrandService->simpleLogoUrl($branch),
            'favicon_url' => $branchBrandService->faviconUrl($branch),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function isLocalhostUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * @return array<string, bool>
     */
    private function serializeEmailNotificationSettings(PlatformSetting $settings): array
    {
        return [
            'email_welcome_enabled' => (bool) $settings->email_welcome_enabled,
            'email_birthday_enabled' => (bool) $settings->email_birthday_enabled,
            'email_offers_enabled' => (bool) $settings->email_offers_enabled,
            'email_marketing_enabled' => (bool) $settings->email_marketing_enabled,
            'email_recovery_enabled' => (bool) $settings->email_recovery_enabled,
        ];
    }

    private function serializePlatformSettings(PlatformSetting $settings): array
    {
        return [
            'library_code' => $settings->library_code,
            'student_code_prefix' => $settings->student_code_prefix,
            'student_code_padding' => $settings->student_code_padding ?: config('libcontrol.defaults.student_code_padding'),
            'sample_student_code' => $this->studentCodeService->preview(),
            'display_name' => $settings->display_name,
            'logo_with_text_url' => $settings->logoWithTextUrl(),
            'simple_logo_url' => $settings->simpleLogoUrl(),
            'logo_url' => $settings->logoUrl(),
            'favicon_url' => $settings->faviconUrl(),
            'id_card_template' => $settings->idCardTemplate(),
            'id_card_logo_url' => $settings->idCardLogoUrl(),
        ];
    }
}
