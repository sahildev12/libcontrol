@php
    $portal = $portalRoutes ?? [];
    $settingsUpdateUrl = $portal['update'] ?? route('settings.update');
    $settingsPlatformUpdateUrl = $portal['platform'] ?? route('settings.platform.update');
    $settingsWebsiteUpdateUrl = $portal['website'] ?? route('settings.website.update');
    $settingsEmailNotificationsUpdateUrl = $portal['email_notifications'] ?? route('settings.email-notifications.update');
    $settingsGlobalUpdateUrl = $portal['global'] ?? route('settings.global.update');
@endphp

<x-admin-layout>
    <div
        x-data="settingsPage({
            settings: @js($settings),
            platformSettings: @js([
                'library_code' => $platformSettings->library_code,
                'student_code_prefix' => $platformSettings->student_code_prefix,
                'student_code_padding' => $platformSettings->student_code_padding ?: config('libcontrol.defaults.student_code_padding'),
                'sample_student_code' => app(\App\Services\StudentCodeService::class)->preview(),
                'display_name' => $platformSettings->display_name,
                'logo_with_text_url' => $platformSettings->logoWithTextUrl(),
                'simple_logo_url' => $platformSettings->simpleLogoUrl(),
                'favicon_url' => $platformSettings->faviconUrl(),
                'id_card_template' => $platformSettings->idCardTemplate(),
                'id_card_logo_url' => $platformSettings->idCardLogoUrl(),
            ]),
            idCardTemplates: @js(config('libcontrol.id_card_templates', [])),
            planSnapshot: @js($planSnapshot),
            planForm: @js([
                'plan_tier' => $platformSettings->planTier(),
                'max_seats_override' => $platformSettings->max_seats_override,
                'max_halls_override' => $platformSettings->max_halls_override,
                'max_branches_override' => $platformSettings->max_branches_override,
            ]),
            planTiers: @js(array_keys(config('libcontrol.plans', []))),
            updateUrl: @js($settingsUpdateUrl),
            platformUpdateUrl: @js($settingsPlatformUpdateUrl),
            platformPlanUpdateUrl: @js(route('settings.platform.plan.update')),
            isPlatformAdmin: @js($isClientAdmin),
            isClientAdmin: @js($isClientAdmin),
            isDeveloperAdmin: @js($isDeveloperAdmin),
            viewingAll: @js($viewingAll ?? false),
            timezone: @js(config('libcontrol.timezone')),
            clearCacheUrl: @js(route('settings.clear-cache')),
            licenseServerEnabled: @js($licenseServerEnabled ?? false),
            deploymentsUrl: @js($deploymentsUrl),
            availableAddons: @js($availableAddons ?? []),
            addonInstallUrl: @js(url('/settings/addons')),
            databaseStatus: @js($isDeveloperAdmin ? $databaseMaintenance->status() : null),
            databaseBackups: @js($isDeveloperAdmin ? $databaseMaintenance->listBackups() : []),
            databaseStatusUrl: @js(route('settings.database.status')),
            databaseBackupUrl: @js(route('settings.database.backup')),
            databaseMigrateUrl: @js(route('settings.database.migrate')),
            databaseRestoreUrl: @js(route('settings.database.restore')),
            databaseDeleteUrl: @js(route('settings.database.backup.destroy')),
            databaseDownloadUrl: @js(route('settings.database.download')),
            deploymentInfo: @js($deploymentInfo ?? []),
            syncRuntimeUrl: @js(route('settings.sync-runtime')),
            websiteSettings: @js($websiteSettings ?? []),
            websiteUpdateUrl: @js($settingsWebsiteUpdateUrl),
            emailNotificationSettings: @js($emailNotificationSettings ?? []),
            emailNotificationsUpdateUrl: @js($settingsEmailNotificationsUpdateUrl),
            globalUpdateUrl: @js($settingsGlobalUpdateUrl),
            portalContext: @js($portalContext ?? false),
            globalExpiryReminderDays: @js($globalExpiryReminderDays),
        })"
        x-init="init()"
    >
        @if ($portalContext ?? false)
            <div class="mb-5 overflow-hidden rounded-xl border border-indigo-200 bg-indigo-50 shadow-sm">
                <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Portal settings</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-900">Managing {{ $managedTenant['client_name'] }}</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            <a href="{{ $managedTenant['url'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">{{ $managedTenant['host'] }}</a>
                            · Scope:
                            @if ($viewingAll ?? false)
                                All branches (platform admin)
                            @elseif ($branch)
                                {{ $branch->display_name ?: $branch->name }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-end gap-2">
                        <form method="POST" action="{{ $portal['switch_branch'] }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Settings scope</label>
                                <select name="branch_id" class="mt-1 min-w-[12rem] rounded-lg border border-gray-300 py-2 pl-3 pr-10 text-sm">
                                    <option value="" @selected(($portalBranchId ?? null) === null)>All branches</option>
                                    @foreach ($portalBranches as $portalBranch)
                                        <option value="{{ $portalBranch['id'] }}" @selected((int) ($portalBranchId ?? 0) === (int) $portalBranch['id'])>
                                            {{ $portalBranch['display_name'] ?: $portalBranch['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="inline-flex h-[42px] items-center rounded-lg border border-indigo-300 bg-white px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Apply</button>
                        </form>
                        <a href="{{ $portal['index'] }}" class="inline-flex h-[42px] items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Change library</a>
                    </div>
                </div>
            </div>
        @endif

        <header>
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if ($portalContext ?? false)
                    @if ($viewingAll ?? false)
                        Platform-wide settings for this hosted library. Switch scope to edit a specific branch.
                    @else
                        Branch settings for {{ $branch?->display_name ?: $branch?->name }}.
                    @endif
                @elseif ($viewingAll ?? false)
                    Library-wide settings for all branches. Choose a specific branch to edit opening hours or student ID prefixes.
                @elseif ($isClientAdmin && $branch)
                    Library hours for {{ $branch->display_name ?: $branch->name }}.
                @elseif ($branch)
                    Library hours and expiry reminders for {{ $branch->display_name ?: $branch->name }}.
                @endif
            </p>
        </header>

        @if ($isClientAdmin || ($isDeveloperAdmin && ($isHub ?? false)))
            <div class="mt-5 flex flex-wrap gap-2 border-b border-gray-200">
                @if ($isClientAdmin)
                <button
                    type="button"
                    @click="settingsTab = 'general'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'general' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >General</button>
                @if ($viewingAll ?? false)
                <button
                    type="button"
                    @click="settingsTab = 'id-cards'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'id-cards' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >ID Cards</button>
                @endif
                <button
                    type="button"
                    @click="settingsTab = 'website'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'website' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >Website</button>
                @if ($viewingAll ?? false)
                <button
                    type="button"
                    @click="settingsTab = 'emails'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'emails' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >Emails</button>
                @endif
                @endif
                @if ($isDeveloperAdmin && ($isHub ?? false))
                <button
                    type="button"
                    @click="settingsTab = 'subscription'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'subscription' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >Subscription</button>
                <button
                    type="button"
                    @click="settingsTab = 'addons'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'addons' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >Addons</button>
                <button
                    type="button"
                    @click="settingsTab = 'developer'; refreshDatabaseStatus()"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'developer' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >Developer</button>
                @endif
            </div>
        @endif

        <form x-show="showGeneralSettingsForm()" @submit.prevent="saveSettings()" class="mt-4 space-y-6">
            @if ($isClientAdmin)
                <section class="overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">
                    <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Library code</h2>
                        <p class="mt-1 text-xs text-gray-600">Auto-generated code for the student mobile app. Share this with students who connect manually.</p>
                    </div>
                    <div class="p-5">
                        <code class="inline-block rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-lg font-bold tracking-widest text-emerald-900" x-text="platformSettings.library_code || '—'"></code>
                    </div>
                </section>

                @unless ($viewingAll ?? false)
                <section class="overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">
                    <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Student ID numbers</h2>
                        <p class="mt-1 text-xs text-gray-600">Default student ID style for this library. Each branch can override its own prefix from the Branch page.</p>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Letters at the start <span class="text-red-500">*</span></label>
                            <input type="text" x-model="platformForm.student_code_prefix" required pattern="[A-Za-z0-9_-]+" maxlength="20" placeholder="PIT" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm uppercase">
                            <p class="mt-1 text-xs text-gray-500">Example: PIT in PIT-001.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">How many digits <span class="text-red-500">*</span></label>
                            <input type="number" min="1" max="6" x-model.number="platformForm.student_code_padding" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">3 digits makes 001, 002, 003…</p>
                        </div>
                        <div class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                            Next student ID will look like: <span class="font-bold" x-text="previewCode()"></span>
                        </div>
                    </div>
                </section>
                @endunless

            @endif

            @if ($branch && $settings && ! ($viewingAll ?? false))
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Library hours</h2>
                    <p class="mt-1 text-xs text-gray-500">Controls time slots, seat availability windows, and trial booking hours.</p>
                </div>
                <div class="space-y-4 p-5">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" x-model="form.is_open_24_hours" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Open 24 hours
                    </label>
                    <div class="grid gap-4 md:grid-cols-2" x-show="! form.is_open_24_hours">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Opening time</label>
                            <input type="time" x-model="form.library_open_time" class="mt-1 block w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Closing time</label>
                            <input type="time" x-model="form.library_close_time" class="mt-1 block w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        <p class="font-medium text-gray-900">Current slot windows</p>
                        <ul class="mt-2 space-y-1 text-xs" x-show="form.is_open_24_hours">
                            <li>Full Day (open 24 hours)</li>
                            <li>Custom Hours (any time · open 24 hours)</li>
                        </ul>
                        <ul class="mt-2 space-y-1 text-xs" x-show="! form.is_open_24_hours">
                            <li x-text="`Full Day (${formatClock(form.library_open_time)} – ${formatClock(form.library_close_time)})`"></li>
                            <li x-text="`Custom Hours (${formatClock(form.library_open_time)} – ${formatClock(form.library_close_time)})`"></li>
                        </ul>
                    </div>
                </div>
            </section>
            @endif

            @if (($viewingAll ?? false) && $isClientAdmin)
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Plan expiry emails</h2>
                    <p class="mt-1 text-xs text-gray-500">A reminder email is sent in the morning, this many days before a plan ends. Applies to all branches.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div class="max-w-xs">
                        <label class="block text-sm font-medium text-gray-700">Send reminder days before expiry</label>
                        <input type="number" min="1" max="90" x-model.number="form.expiry_reminder_days" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                </div>
            </section>
            @endif

            <div class="flex justify-end">
                <button type="submit" :disabled="saving" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save Settings'"></button>
            </div>
        </form>

        @if ($isClientAdmin && ($viewingAll ?? false))
        <form x-show="settingsTab === 'id-cards'" x-cloak @submit.prevent="saveIdCardSettings()" class="mt-4 space-y-6">
            <section class="overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm">
                <div class="border-b border-violet-100 bg-violet-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Student ID cards</h2>
                    <p class="mt-1 text-xs text-gray-600">Choose a print template and upload your library logo for student ID cards. System branding stays managed by LibControl.</p>
                </div>
                <div class="space-y-5 p-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Library logo for ID cards</label>
                        <p class="mt-0.5 text-xs text-gray-500">Shown on the selected template when printing student ID cards.</p>
                        <input type="file" @change="platformForm.id_card_logo = $event.target.files[0]" accept=".jpg,.jpeg,.png,.svg,.webp" class="mt-2 block w-full max-w-md text-sm text-gray-600">
                        <img x-show="platformSettings.id_card_logo_url" :src="platformSettings.id_card_logo_url" alt="" class="mt-3 h-12 max-w-[12rem] object-contain rounded border border-gray-200 bg-gray-50 p-2">
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">ID card template</p>
                        <p class="mt-0.5 text-xs text-gray-500">Pick one of three HTML layouts. All templates are print-ready (CR80 size).</p>
                        <div class="mt-4 grid gap-4 lg:grid-cols-3">
                            @foreach (config('libcontrol.id_card_templates', []) as $templateKey => $templateMeta)
                                <button
                                    type="button"
                                    @click="platformForm.id_card_template = '{{ $templateKey }}'"
                                    class="relative rounded-xl border p-4 text-left transition-colors"
                                    :class="platformForm.id_card_template === '{{ $templateKey }}' ? 'border-violet-500 bg-violet-50/40 ring-2 ring-violet-300' : 'border-gray-200 bg-white hover:border-gray-300'"
                                >
                                    <span
                                        x-show="platformForm.id_card_template === '{{ $templateKey }}'"
                                        x-cloak
                                        class="absolute right-3 top-3 z-10 rounded-full bg-violet-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                                    >Selected</span>
                                    <p class="pr-16 text-sm font-semibold text-gray-900">{{ $templateMeta['label'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $templateMeta['description'] }}</p>
                                    <x-admin.student-id-card-preview
                                        :template="$templateKey"
                                        :logo-url="$platformSettings->idCardLogoUrl()"
                                    />
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" :disabled="savingIdCards" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="savingIdCards ? 'Saving...' : 'Save ID Card Settings'"></button>
            </div>
        </form>
        @endif

        @if ($isDeveloperAdmin && ($isHub ?? false))
        <form x-show="settingsTab === 'subscription'" x-cloak @submit.prevent="savePlanSettings()" class="mt-4 space-y-6">
            <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Subscription plan &amp; limits</h2>
                    <p class="mt-1 text-xs text-gray-600">Controls how many branches, halls, and seats this installation can use.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Branches</p>
                            <p class="mt-1 font-bold text-gray-900">
                                <span x-text="planSnapshot.usage.branches"></span>
                                <span class="font-normal text-gray-500">/</span>
                                <span x-text="planSnapshot.limits.max_branches ?? '∞'"></span>
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Halls</p>
                            <p class="mt-1 font-bold text-gray-900">
                                <span x-text="planSnapshot.usage.halls"></span>
                                <span class="font-normal text-gray-500">/</span>
                                <span x-text="planSnapshot.limits.max_halls ?? '∞'"></span>
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Seats</p>
                            <p class="mt-1 font-bold text-gray-900">
                                <span x-text="planSnapshot.usage.seats"></span>
                                <span class="font-normal text-gray-500">/</span>
                                <span x-text="planSnapshot.limits.max_seats ?? '∞'"></span>
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Plan tier</label>
                            <select x-model="planForm.plan_tier" class="admin-select mt-1 block w-full px-3 py-2">
                                <template x-for="tier in planTiers" :key="tier">
                                    <option :value="tier" x-text="tier.charAt(0).toUpperCase() + tier.slice(1)"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Custom seat limit</label>
                            <input type="number" min="1" x-model.number="planForm.max_seats_override" placeholder="Leave blank for plan default" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Custom hall limit</label>
                            <input type="number" min="1" x-model.number="planForm.max_halls_override" placeholder="Leave blank for plan default" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Custom branch limit</label>
                            <input type="number" min="1" x-model.number="planForm.max_branches_override" placeholder="Leave blank for plan default" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" :disabled="savingPlan" class="inline-flex h-10 items-center rounded-lg bg-amber-500 px-5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50" x-text="savingPlan ? 'Saving...' : 'Save Subscription Settings'"></button>
            </div>
        </form>

        <section x-show="settingsTab === 'addons'" x-cloak class="mt-4 space-y-6">
            <section class="overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm">
                <div class="border-b border-violet-100 bg-violet-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Addon catalog</h2>
                    <p class="mt-1 text-xs text-gray-600">All available addons are listed below. Click Install to enable one instantly — it will appear in the sidebar when active.</p>
                </div>
                <div class="p-5">
                    <div class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                        @forelse ($availableAddons as $addon)
                            <div class="flex flex-wrap items-start justify-between gap-4 p-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">{{ $addon['name'] }}</p>
                                    <p class="mt-1 text-sm text-gray-600">{{ $addon['description'] }}</p>
                                    <p class="mt-2 text-xs text-gray-500">
                                        v{{ $addon['version'] }}
                                        @if ($addon['installed'])
                                            · Installed @if ($addon['installed_version'])(v{{ $addon['installed_version'] }})@endif
                                        @else
                                            · <span class="font-semibold text-gray-500">Not installed</span>
                                        @endif
                                        @if ($addon['enabled'])
                                            · <span class="font-semibold text-emerald-600">Enabled</span>
                                        @elseif ($addon['installed'])
                                            · <span class="font-semibold text-amber-600">Disabled</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if (! $addon['installed'])
                                        <button type="button" @click="installAddon('{{ $addon['slug'] }}')" :disabled="addonBusy" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                            <span x-show="! addonBusy">Install</span>
                                            <span x-show="addonBusy">Installing...</span>
                                        </button>
                                    @elseif (! $addon['enabled'])
                                        <button type="button" @click="enableAddon('{{ $addon['slug'] }}')" :disabled="addonBusy" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 disabled:opacity-50">Enable</button>
                                    @else
                                        <button type="button" @click="disableAddon('{{ $addon['slug'] }}')" :disabled="addonBusy" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100 disabled:opacity-50">Disable</button>
                                        @if ($addon['settings_url'])
                                            <a href="{{ $addon['settings_url'] }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Open</a>
                                        @endif
                                    @endif
                                    @if ($addon['installed'])
                                        <button type="button" @click="uninstallAddon('{{ $addon['slug'] }}')" :disabled="addonBusy" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50">Uninstall</button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-gray-500">No addons available in the catalog yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </section>

        <section x-show="settingsTab === 'developer'" x-cloak class="mt-4 space-y-6">
            <section class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Developer tools</h2>
                    <p class="mt-1 text-xs text-gray-600">Cache, Phenomit sync, and deployment registry (license server only).</p>
                </div>
                <div class="flex flex-wrap gap-3 p-5">
                    <button
                        type="button"
                        @click="clearApplicationCache()"
                        :disabled="clearingCache"
                        class="inline-flex h-10 items-center rounded-lg bg-slate-800 px-5 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-50"
                        x-text="clearingCache ? 'Clearing cache...' : 'Clear application cache'"
                    ></button>
                    <button
                        type="button"
                        @click="syncRuntimeMetrics()"
                        :disabled="syncingRuntime"
                        class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-50"
                        x-text="syncingRuntime ? 'Syncing...' : 'Sync library to Phenomit'"
                    ></button>
                    @if ($deploymentsUrl)
                        <a
                            href="{{ $deploymentsUrl }}"
                            class="inline-flex h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100"
                        >
                            Dev &amp; Domains registry
                        </a>
                    @endif
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">
                <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Student registration contact rules</h2>
                    <p class="mt-1 text-xs text-gray-600">Require phone number and email address when adding students. Linked siblings can share one family contact.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div x-show="viewingAll" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                        Select a specific branch from the top bar before changing this setting.
                    </div>
                    <div x-show="! viewingAll && settings" class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        Applying to branch:
                        <span class="font-semibold text-gray-900">{{ $branch?->display_name ?: $branch?->name }}</span>
                    </div>
                    <label class="inline-flex items-start gap-3 text-sm text-gray-700" :class="viewingAll ? 'opacity-60' : ''">
                        <input
                            type="checkbox"
                            x-model="form.require_student_contact"
                            :disabled="viewingAll"
                            class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50"
                        >
                        <span>
                            <span class="font-medium text-gray-900">Require phone number and email address</span>
                            <span class="mt-1 block text-xs text-gray-500">When enabled, every new student must have both a phone number and email on registration. Siblings linked to an existing family contact can skip these fields.</span>
                        </span>
                    </label>
                    <div class="flex justify-end">
                        <button
                            type="button"
                            @click="saveStudentContactRules()"
                            :disabled="contactRulesSaving || viewingAll"
                            class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <span x-show="! contactRulesSaving">Save contact rules</span>
                            <span x-show="contactRulesSaving">Saving...</span>
                        </button>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Database</p>
                    <p class="mt-2 text-sm font-medium text-gray-900" x-text="databaseStatus?.database || '—'"></p>
                    <p class="mt-1 text-xs text-gray-500">Driver: <span x-text="databaseStatus?.driver || '—'"></span></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Pending migrations</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900" x-text="databaseStatus?.pending_migrations ?? 0"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Backups on server</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900" x-text="databaseBackups.length"></p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <p class="text-sm font-semibold text-gray-900">Step 1 · Backup</p>
                        <p class="mt-1 text-xs text-gray-500">Save a full copy before updating.</p>
                    </div>
                    <div class="space-y-3 p-5">
                        <button type="button" @click="createDatabaseBackup()" :disabled="databaseBusy" class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                            <span x-show="! databaseBusy">Create backup now</span>
                            <span x-show="databaseBusy">Working...</span>
                        </button>
                        <p class="text-xs text-gray-500" x-show="lastBackupMessage" x-text="lastBackupMessage"></p>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <p class="text-sm font-semibold text-gray-900">Step 2 · Migrate</p>
                        <p class="mt-1 text-xs text-gray-500">Apply new database changes from the update.</p>
                    </div>
                    <div class="space-y-3 p-5">
                        <button type="button" @click="runDatabaseMigrations()" :disabled="databaseBusy" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-sm font-semibold text-emerald-800 hover:bg-emerald-100 disabled:opacity-50">
                            Run migrations
                        </button>
                        <pre x-show="migrationOutput" class="max-h-32 overflow-auto rounded-lg bg-gray-50 p-3 text-[11px] text-gray-700" x-text="migrationOutput"></pre>
                    </div>
                </section>

                <section class="rounded-xl border border-rose-200 bg-white shadow-sm">
                    <div class="border-b border-rose-100 bg-rose-50 px-5 py-4">
                        <p class="text-sm font-semibold text-gray-900">Step 3 · Restore (emergency)</p>
                        <p class="mt-1 text-xs text-gray-500">Only if migration or update failed.</p>
                    </div>
                    <div class="space-y-3 p-5">
                        <select x-model="restoreFilename" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Select a backup...</option>
                            <template x-for="backup in databaseBackups" :key="backup.filename">
                                <option :value="backup.filename" x-text="`${backup.created_at} · ${backup.size_label}`"></option>
                            </template>
                        </select>
                        <input type="text" x-model="restoreConfirmation" placeholder="Type RESTORE to confirm" class="w-full rounded-lg border-gray-300 text-sm">
                        <button type="button" @click="restoreDatabaseBackup()" :disabled="databaseBusy || ! restoreFilename || restoreConfirmation !== 'RESTORE'" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-rose-300 bg-rose-50 text-sm font-semibold text-rose-800 hover:bg-rose-100 disabled:opacity-50">
                            Restore selected backup
                        </button>
                    </div>
                </section>
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Saved backups</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <template x-for="backup in databaseBackups" :key="backup.filename">
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div>
                                <p class="font-medium text-gray-900" x-text="backup.filename"></p>
                                <p class="text-xs text-gray-500" x-text="`${backup.created_at} · ${backup.size_label}`"></p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a :href="`${databaseDownloadUrl}?filename=${encodeURIComponent(backup.filename)}`" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Download</a>
                                <button type="button" @click="deleteDatabaseBackup(backup.filename)" :disabled="databaseBusy" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50">Delete</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="databaseBackups.length === 0" class="px-5 py-8 text-center text-sm text-gray-500">No backups yet. Create one before your next update.</p>
                </div>
            </section>
        </section>
        @endif

        @if ($isClientAdmin)
            @include('settings.partials.website-tab')
            @if ($viewingAll ?? false)
                @include('settings.partials.emails-tab')
            @endif
        @endif
    </div>
</x-admin-layout>
