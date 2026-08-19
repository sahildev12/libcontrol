<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBranchSettingsRequest;
use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Services\BranchBrandService;
use App\Services\LibraryScheduleService;
use App\Services\StudentCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private StudentCodeService $studentCodeService,
    ) {}

    public function index(Request $request, BranchBrandService $branchBrandService): View
    {
        $branch = $this->activeBranch($request);

        abort_unless($branch, 403);

        $settings = $this->serializeSettings($branch, $branchBrandService);
        $platformSettings = PlatformSetting::current();
        $isPlatformAdmin = (bool) $request->user()?->isPlatformAdmin();

        return view('settings.index', compact('branch', 'settings', 'platformSettings', 'isPlatformAdmin'));
    }

    public function update(UpdateBranchSettingsRequest $request, BranchBrandService $branchBrandService): JsonResponse
    {
        /** @var Branch $branch */
        $branch = $this->activeBranch($request);

        abort_unless($branch, 403);

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

    public function updatePlatform(UpdatePlatformSettingsRequest $request): JsonResponse
    {
        $settings = PlatformSetting::current();
        $settings->update($request->validated());

        return response()->json([
            'message' => 'Global student code settings saved.',
            'platform_settings' => $this->serializePlatformSettings($settings->fresh()),
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
        ];
    }
}
