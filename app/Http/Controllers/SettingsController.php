<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBranchSettingsRequest;
use App\Http\Requests\UpdatePlatformPlanRequest;
use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Services\BranchBrandService;
use App\Services\LibraryScheduleService;
use App\Services\LoginBrandingService;
use App\Services\PlanLimitService;
use App\Services\StudentCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private StudentCodeService $studentCodeService,
        private PlanLimitService $planLimitService,
    ) {}

    public function index(Request $request, BranchBrandService $branchBrandService): View
    {
        $branch = $this->optionalActiveBranch($request);
        $viewingAll = $this->viewingAllBranches($request);

        abort_unless($branch || $request->user()?->isPlatformAdmin(), 403);

        $settings = $branch ? $this->serializeSettings($branch, $branchBrandService) : null;
        $platformSettings = PlatformSetting::current();
        $isPlatformAdmin = (bool) $request->user()?->isPlatformAdmin();
        $isDeveloperAdmin = (bool) $request->user()?->isDeveloperAdmin();
        $planSnapshot = $this->planLimitService->snapshot();
        $licenseServerEnabled = (bool) config('libspace.license_server.enabled');

        return view('settings.index', compact('branch', 'settings', 'platformSettings', 'isPlatformAdmin', 'isDeveloperAdmin', 'planSnapshot', 'viewingAll', 'licenseServerEnabled'));
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

        if ($request->hasFile('logo_with_text')) {
            $data['logo_with_text_path'] = $branchBrandService->storeUpload($branch, $request->file('logo_with_text'), 'logo_with_text');
        }

        if ($request->hasFile('simple_logo')) {
            $data['simple_logo_path'] = $branchBrandService->storeUpload($branch, $request->file('simple_logo'), 'simple_logo');
        }

        if ($request->hasFile('favicon')) {
            $data['favicon_path'] = $branchBrandService->storeUpload($branch, $request->file('favicon'), 'favicon');
        }

        $branch->update($data);

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => $this->serializeSettings($branch->fresh(), $branchBrandService),
        ]);
    }

    public function updatePlatform(UpdatePlatformSettingsRequest $request, LoginBrandingService $loginBranding): JsonResponse
    {
        $settings = PlatformSetting::current();
        $data = $request->safe()->except(['logo', 'favicon']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $loginBranding->storePlatformLogo($request->file('logo'), 'logo');
        }

        if ($request->hasFile('favicon')) {
            $data['favicon_path'] = $loginBranding->storePlatformLogo($request->file('favicon'), 'favicon');
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
            'expiry_reminder_days' => $branch->expiry_reminder_days ?: config('libspace.defaults.expiry_reminder_days'),
            'library_open_time' => $branch->library_open_time ? substr((string) $branch->library_open_time, 0, 5) : '09:00',
            'library_close_time' => $branch->library_close_time ? substr((string) $branch->library_close_time, 0, 5) : '18:00',
            'is_open_24_hours' => (bool) $branch->is_open_24_hours,
            'time_slot_options' => LibraryScheduleService::forBranch($branch)->timeSlotOptions(),
            'logo_with_text_url' => $branchBrandService->logoWithTextUrl($branch),
            'simple_logo_url' => $branchBrandService->simpleLogoUrl($branch),
            'favicon_url' => $branchBrandService->faviconUrl($branch),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlatformSettings(PlatformSetting $settings): array
    {
        return [
            'student_code_prefix' => $settings->student_code_prefix,
            'student_code_padding' => $settings->student_code_padding ?: config('libspace.defaults.student_code_padding'),
            'sample_student_code' => $this->studentCodeService->preview(),
            'display_name' => $settings->display_name,
            'logo_url' => $settings->logoUrl(),
            'favicon_url' => $settings->faviconUrl(),
        ];
    }
}
