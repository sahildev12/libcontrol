<?php

namespace App\Services\Developer;

use App\Http\Requests\UpdateBranchSettingsRequest;
use App\Http\Requests\UpdatePlatformSettingsRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LibraryWebsiteService;
use App\Services\MailDeliveryService;
use App\Services\PlanLimitService;
use App\Services\PlatformBrandService;
use App\Services\Settings\SettingsPayloadService;
use App\Services\Tenancy\TenantConnectionManager;
use Illuminate\Http\Request;

class TenantPortalSettingsService
{
    public const SESSION_TENANT_KEY = 'developer_portal_tenant_id';

    public const SESSION_BRANCH_KEY = 'developer_portal_branch_id';

    public function __construct(
        private TenantConnectionManager $connections,
        private SettingsPayloadService $settingsPayload,
        private PlanLimitService $planLimitService,
        private LibraryWebsiteService $libraryWebsiteService,
        private MailDeliveryService $mailDelivery,
        private PlatformBrandService $platformBrand,
    ) {}

    public function ensureManageable(Tenant $tenant): void
    {
        abort_unless($tenant->active, 404);
        abort_unless($tenant->provisioned_at, 403, 'This library has not been provisioned yet.');
    }

    /**
     * @return array<int, array{id: int, name: string, display_name: string|null}>
     */
    public function branchOptions(Tenant $tenant): array
    {
        return $this->connections->runOnTenant($tenant, function () {
            return Branch::query()
                ->orderBy('name')
                ->get(['id', 'name', 'display_name'])
                ->map(fn (Branch $branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'display_name' => $branch->display_name,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsViewData(Tenant $tenant, Request $request): array
    {
        $this->ensureManageable($tenant);
        $this->rememberTenant($request, $tenant);

        $branchId = $this->resolvedBranchId($request, $tenant);
        $viewingAll = $branchId === null;

        $portalBranches = $this->branchOptions($tenant);

        return $this->connections->runOnTenant($tenant, function () use ($tenant, $branchId, $viewingAll, $portalBranches) {
            $branch = $branchId ? Branch::query()->find($branchId) : null;
            abort_if($branchId && ! $branch, 404);

            $platformSettings = PlatformSetting::current();
            $settings = $branch ? $this->settingsPayload->serializeBranchSettings($branch) : null;
            $planSnapshot = $this->planLimitService->snapshot();
            $websiteSettings = $this->libraryWebsiteService->settingsPayload($platformSettings);
            $emailNotificationSettings = $this->settingsPayload->serializeEmailNotificationSettings($platformSettings);
            $globalExpiryReminderDays = $viewingAll
                ? (int) (Branch::query()->value('expiry_reminder_days') ?: config('libcontrol.defaults.expiry_reminder_days', 10))
                : null;

            $appUrl = rtrim((string) config('app.url'), '/');
            $deploymentInfo = [
                'public_url' => $appUrl,
                'app_url' => $appUrl,
                'library_code' => $platformSettings->library_code,
                'sync_endpoint' => config('libcontrol.deployment.sync_endpoint'),
                'public_url_is_localhost' => $this->isLocalhostUrl($appUrl),
            ];

            return [
                'branch' => $branch,
                'settings' => $settings,
                'platformSettings' => $platformSettings,
                'isPlatformAdmin' => true,
                'isClientAdmin' => true,
                'isDeveloperAdmin' => false,
                'isHub' => false,
                'planSnapshot' => $planSnapshot,
                'viewingAll' => $viewingAll,
                'licenseServerEnabled' => false,
                'deploymentsUrl' => null,
                'availableAddons' => [],
                'deploymentInfo' => $deploymentInfo,
                'websiteSettings' => $websiteSettings,
                'emailNotificationSettings' => $emailNotificationSettings,
                'mailDeliveryStatus' => $this->mailDelivery->status(),
                'globalExpiryReminderDays' => $globalExpiryReminderDays,
                'portalContext' => true,
                'managedTenant' => [
                    'id' => $tenant->id,
                    'client_name' => $tenant->client_name,
                    'subdomain' => $tenant->subdomain,
                    'host' => $tenant->host(),
                    'url' => $tenant->url(),
                ],
                'portalBranches' => $portalBranches,
                'portalBranchId' => $branchId,
                'portalRoutes' => $this->routeMap($tenant),
            ];
        });
    }

    public function switchBranch(Request $request, Tenant $tenant): void
    {
        $this->ensureManageable($tenant);
        $this->rememberTenant($request, $tenant);

        $branchId = $request->input('branch_id');
        if ($branchId === '' || $branchId === null) {
            $request->session()->put(self::SESSION_BRANCH_KEY, null);

            return;
        }

        $branchId = (int) $branchId;
        $exists = $this->connections->runOnTenant($tenant, fn () => Branch::query()->whereKey($branchId)->exists());
        abort_unless($exists, 422, 'Invalid branch selected.');

        $request->session()->put(self::SESSION_BRANCH_KEY, $branchId);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateBranchSettings(Tenant $tenant, Request $request, ?User $user): array
    {
        $this->ensureManageable($tenant);
        $branchId = $this->resolvedBranchId($request, $tenant);
        abort_unless($branchId, 422, 'Select a specific branch to update library hours.');

        $input = $request->all();
        if (array_key_exists('is_open_24_hours', $input)) {
            $input['is_open_24_hours'] = filter_var($input['is_open_24_hours'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('require_student_contact', $input)) {
            $input['require_student_contact'] = filter_var($input['require_student_contact'], FILTER_VALIDATE_BOOLEAN);
        }

        $validated = Validator::make($input, (new UpdateBranchSettingsRequest)->rules())->validate();

        $result = $this->connections->runOnTenant($tenant, function () use ($branchId, $validated) {
            $branch = Branch::query()->findOrFail($branchId);
            $data = collect($validated)->except(['logo_with_text', 'simple_logo', 'favicon'])->all();
            $branch->update($data);

            return [
                'message' => 'Settings saved.',
                'settings' => $this->settingsPayload->serializeBranchSettings($branch->fresh()),
                'branch_name' => $branch->name,
            ];
        });

        app(ActivityLogger::class)->record(
            $user,
            'portal.branch_settings_updated',
            "Updated branch settings for {$tenant->client_name} ({$result['branch_name']}).",
            $tenant,
            null,
            ['tenant_id' => $tenant->id, 'branch_id' => $branchId],
            $request,
        );

        return [
            'message' => $result['message'],
            'settings' => $result['settings'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updatePlatformSettings(Tenant $tenant, Request $request, ?User $user): array
    {
        $this->ensureManageable($tenant);
        abort_unless($this->resolvedBranchId($request, $tenant) === null, 422, 'Switch to all branches to update platform settings.');

        $validated = Validator::make($request->all(), (new UpdatePlatformSettingsRequest)->rules())->validate();

        $result = $this->connections->runOnTenant($tenant, function () use ($validated, $request) {
            $settings = PlatformSetting::current();
            $data = collect($validated)->except(['id_card_logo'])->all();

            if ($request->hasFile('id_card_logo')) {
                $data['id_card_logo_path'] = $this->platformBrand->storeUpload($request->file('id_card_logo'), 'id_card_logo');
            }

            $settings->update($data);

            return [
                'message' => 'Global settings saved.',
                'platform_settings' => $this->settingsPayload->serializePlatformSettings($settings->fresh()),
            ];
        });

        app(ActivityLogger::class)->record(
            $user,
            'portal.platform_settings_updated',
            "Updated platform settings for {$tenant->client_name}.",
            $tenant,
            null,
            ['tenant_id' => $tenant->id],
            $request,
        );

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function updateWebsiteSettings(Tenant $tenant, Request $request, ?User $user): array
    {
        $this->ensureManageable($tenant);
        abort_unless($this->resolvedBranchId($request, $tenant) === null, 422, 'Switch to all branches to update website settings.');

        $validated = $request->validate([
            'website_enabled' => ['nullable', 'boolean'],
            'website_tagline' => ['nullable', 'string', 'max:255'],
            'website_hero_title' => ['nullable', 'string', 'max:255'],
            'website_about' => ['nullable', 'string', 'max:5000'],
            'website_amenities' => ['nullable', 'array'],
            'website_amenities.*' => ['nullable', 'string', 'max:120'],
            'website_social_links' => ['nullable', 'array'],
            'website_social_links.facebook' => ['nullable', 'url', 'max:255'],
            'website_social_links.instagram' => ['nullable', 'url', 'max:255'],
            'website_social_links.youtube' => ['nullable', 'url', 'max:255'],
            'website_social_links.twitter' => ['nullable', 'url', 'max:255'],
            'website_social_links.website' => ['nullable', 'url', 'max:255'],
            'website_whatsapp' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'],
            'website_logo' => ['nullable', 'image', 'max:4096'],
        ]);

        $result = $this->connections->runOnTenant($tenant, function () use ($validated, $request) {
            $settings = PlatformSetting::current();
            $data = collect($validated)->except(['website_logo'])->all();
            $data['website_enabled'] = $request->boolean('website_enabled');

            if (array_key_exists('website_amenities', $data)) {
                $data['website_amenities'] = array_values(array_filter(array_map(
                    static fn ($item) => trim((string) $item),
                    $data['website_amenities'] ?? [],
                )));
            }

            if ($request->hasFile('website_logo')) {
                $data['website_logo_path'] = $this->libraryWebsiteService->storeLogo($request->file('website_logo'));
            }

            $settings->update($data);

            return [
                'message' => 'Website settings saved.',
                'website' => $this->libraryWebsiteService->settingsPayload($settings->fresh()),
            ];
        });

        app(ActivityLogger::class)->record(
            $user,
            'portal.website_settings_updated',
            "Updated website settings for {$tenant->client_name}.",
            $tenant,
            null,
            ['tenant_id' => $tenant->id],
            $request,
        );

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function updateEmailNotifications(Tenant $tenant, Request $request, ?User $user): array
    {
        $this->ensureManageable($tenant);
        abort_unless($this->resolvedBranchId($request, $tenant) === null, 422, 'Switch to all branches to update email notification settings.');

        $validated = $request->validate([
            'email_welcome_enabled' => ['nullable', 'boolean'],
            'email_birthday_enabled' => ['nullable', 'boolean'],
            'email_offers_enabled' => ['nullable', 'boolean'],
            'email_recovery_enabled' => ['nullable', 'boolean'],
        ]);

        $result = $this->connections->runOnTenant($tenant, function () use ($validated) {
            $settings = PlatformSetting::current();
            $settings->update($validated);

            return [
                'message' => 'Email notification settings saved.',
                'email_notifications' => $this->settingsPayload->serializeEmailNotificationSettings($settings->fresh()),
            ];
        });

        app(ActivityLogger::class)->record(
            $user,
            'portal.email_settings_updated',
            "Updated email notification settings for {$tenant->client_name}.",
            $tenant,
            null,
            ['tenant_id' => $tenant->id],
            $request,
        );

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function updateGlobalSettings(Tenant $tenant, Request $request, ?User $user): array
    {
        $this->ensureManageable($tenant);
        abort_unless($this->resolvedBranchId($request, $tenant) === null, 422, 'Switch to all branches to update library-wide settings.');

        $validated = $request->validate([
            'expiry_reminder_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        $result = $this->connections->runOnTenant($tenant, function () use ($validated) {
            Branch::query()->update([
                'expiry_reminder_days' => $validated['expiry_reminder_days'],
            ]);

            return [
                'message' => 'Plan expiry reminder updated for all branches.',
                'expiry_reminder_days' => $validated['expiry_reminder_days'],
            ];
        });

        app(ActivityLogger::class)->record(
            $user,
            'portal.global_settings_updated',
            "Updated global expiry reminder for {$tenant->client_name}.",
            $tenant,
            null,
            ['tenant_id' => $tenant->id],
            $request,
        );

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function routeMap(Tenant $tenant): array
    {
        return [
            'update' => route('developer.portals.settings.branch.update', $tenant),
            'platform' => route('developer.portals.settings.platform.update', $tenant),
            'website' => route('developer.portals.settings.website.update', $tenant),
            'email_notifications' => route('developer.portals.settings.email-notifications.update', $tenant),
            'global' => route('developer.portals.settings.global.update', $tenant),
            'switch_branch' => route('developer.portals.switch-branch', $tenant),
            'settings' => route('developer.portals.settings', $tenant),
            'index' => route('developer.portals.index'),
        ];
    }

    private function rememberTenant(Request $request, Tenant $tenant): void
    {
        $request->session()->put(self::SESSION_TENANT_KEY, $tenant->id);
    }

    private function resolvedBranchId(Request $request, Tenant $tenant): ?int
    {
        if ((int) $request->session()->get(self::SESSION_TENANT_KEY) !== (int) $tenant->id) {
            $request->session()->put(self::SESSION_BRANCH_KEY, null);
            $request->session()->put(self::SESSION_TENANT_KEY, $tenant->id);
        }

        $branchId = $request->session()->get(self::SESSION_BRANCH_KEY);

        return $branchId === null ? null : (int) $branchId;
    }

    private function isLocalhostUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
