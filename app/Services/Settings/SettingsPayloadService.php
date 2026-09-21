<?php

namespace App\Services\Settings;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Services\BranchBrandService;
use App\Services\LibraryScheduleService;
use App\Services\StudentCodeService;

class SettingsPayloadService
{
    public function __construct(
        private StudentCodeService $studentCodeService,
        private BranchBrandService $branchBrandService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function serializeBranchSettings(Branch $branch): array
    {
        return [
            'display_name' => $branch->display_name,
            'expiry_reminder_days' => $branch->expiry_reminder_days ?: config('libcontrol.defaults.expiry_reminder_days'),
            'library_open_time' => $branch->library_open_time ? substr((string) $branch->library_open_time, 0, 5) : '09:00',
            'library_close_time' => $branch->library_close_time ? substr((string) $branch->library_close_time, 0, 5) : '18:00',
            'is_open_24_hours' => (bool) $branch->is_open_24_hours,
            'require_student_contact' => (bool) $branch->require_student_contact,
            'time_slot_options' => LibraryScheduleService::forBranch($branch)->timeSlotOptions(),
            'logo_with_text_url' => $this->branchBrandService->logoWithTextUrl($branch),
            'simple_logo_url' => $this->branchBrandService->simpleLogoUrl($branch),
            'favicon_url' => $this->branchBrandService->faviconUrl($branch),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function serializeEmailNotificationSettings(PlatformSetting $settings): array
    {
        return [
            'email_welcome_enabled' => (bool) $settings->email_welcome_enabled,
            'email_birthday_enabled' => (bool) $settings->email_birthday_enabled,
            'email_offers_enabled' => (bool) $settings->email_offers_enabled,
            'email_recovery_enabled' => (bool) $settings->email_recovery_enabled,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializePlatformSettings(PlatformSetting $settings): array
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
