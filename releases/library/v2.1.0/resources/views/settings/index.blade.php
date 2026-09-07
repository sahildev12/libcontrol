<x-admin-layout>
    <div
        x-data="settingsPage({
            settings: @js($settings),
            platformSettings: @js([
                'student_code_prefix' => $platformSettings->student_code_prefix,
                'student_code_padding' => $platformSettings->student_code_padding ?: config('libcontrol.defaults.student_code_padding'),
                'sample_student_code' => app(\App\Services\StudentCodeService::class)->preview(),
                'display_name' => $platformSettings->display_name,
                'logo_with_text_url' => $platformSettings->logoWithTextUrl(),
                'simple_logo_url' => $platformSettings->simpleLogoUrl(),
                'favicon_url' => $platformSettings->faviconUrl(),
            ]),
            planSnapshot: @js($planSnapshot),
            planForm: @js([
                'plan_tier' => $platformSettings->planTier(),
                'max_seats_override' => $platformSettings->max_seats_override,
                'max_halls_override' => $platformSettings->max_halls_override,
                'max_branches_override' => $platformSettings->max_branches_override,
            ]),
            planTiers: @js(array_keys(config('libcontrol.plans', []))),
            updateUrl: @js(route('settings.update')),
            platformUpdateUrl: @js(route('settings.platform.update')),
            platformPlanUpdateUrl: @js(route('settings.platform.plan.update')),
            isPlatformAdmin: @js($isPlatformAdmin),
            isDeveloperAdmin: @js($isDeveloperAdmin),
            viewingAll: @js($viewingAll ?? false),
            timezone: @js(config('libcontrol.timezone')),
            clearCacheUrl: @js(route('settings.clear-cache')),
            licenseServerEnabled: @js($licenseServerEnabled ?? false),
            deploymentsUrl: @js($deploymentsUrl),
            addonCatalog: @js($addonCatalog ?? []),
            addonInstallUrl: @js(url('/settings/addons')),
            databaseStatus: @js($isPlatformAdmin ? $databaseMaintenance->status() : null),
            databaseBackups: @js($isPlatformAdmin ? $databaseMaintenance->listBackups() : []),
            databaseStatusUrl: @js(route('settings.database.status')),
            databaseBackupUrl: @js(route('settings.database.backup')),
            databaseMigrateUrl: @js(route('settings.database.migrate')),
            databaseRestoreUrl: @js(route('settings.database.restore')),
            databaseDeleteUrl: @js(route('settings.database.backup.destroy')),
            databaseDownloadUrl: @js(route('settings.database.download')),
        })"
        x-init="init()"
    >
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if ($viewingAll ?? false)
                    Viewing all branches. Choose a specific branch to edit library hours and reminders.
                @elseif ($isPlatformAdmin && $branch)
                    Library hours and reminders for {{ $branch->display_name ?: $branch->name }}.
                @elseif ($branch)
                    Library hours and expiry reminders for {{ $branch->display_name ?: $branch->name }}.
                @endif
            </p>
        </header>

        @if ($isPlatformAdmin)
            <div class="mt-5 flex flex-wrap gap-2 border-b border-gray-200">
                <button
                    type="button"
                    @click="settingsTab = 'general'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'general' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >General</button>
                <button
                    type="button"
                    @click="settingsTab = 'database'; refreshDatabaseStatus()"
                    class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                    :class="settingsTab === 'database' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                >Database</button>
            </div>
        @endif

        <form x-show="settingsTab === 'general'" @submit.prevent="saveSettings()" class="mt-4 space-y-6">
            @if ($isPlatformAdmin)
                <section class="overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">
                    <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Student ID numbers</h2>
                        <p class="mt-1 text-xs text-gray-600">Every new student gets an ID like this. All libraries share the same series.</p>
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

                        <template x-if="isDeveloperAdmin">
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
                                <div class="md:col-span-2 flex justify-end">
                                    <button type="button" @click="savePlanSettings()" :disabled="savingPlan" class="inline-flex h-10 items-center rounded-lg bg-amber-500 px-5 text-sm font-semibold text-white hover:bg-amber-600 disabled:opacity-50" x-text="savingPlan ? 'Saving plan...' : 'Save Plan Settings'"></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Library branding</h2>
                        <p class="mt-1 text-xs text-gray-500">Name and logos used across admin, branch login, sidebar, and browser tab. Branches cannot upload their own logos.</p>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Library name</label>
                            <input type="text" x-model="platformForm.display_name" placeholder="Dise Library" class="mt-1 block w-full max-w-md rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Logo with text</label>
                                <p class="mt-0.5 text-xs text-gray-500">Wide logo for login pages.</p>
                                <input type="file" @change="platformForm.logo_with_text = $event.target.files[0]" accept=".jpg,.jpeg,.png,.svg,.webp" class="mt-2 block w-full text-sm text-gray-600">
                                <img x-show="platformSettings.logo_with_text_url" :src="platformSettings.logo_with_text_url" alt="" class="mt-2 h-12 max-w-full object-contain">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Simple logo</label>
                                <p class="mt-0.5 text-xs text-gray-500">Icon-only logo for sidebar.</p>
                                <input type="file" @change="platformForm.simple_logo = $event.target.files[0]" accept=".jpg,.jpeg,.png,.svg,.webp" class="mt-2 block w-full text-sm text-gray-600">
                                <img x-show="platformSettings.simple_logo_url" :src="platformSettings.simple_logo_url" alt="" class="mt-2 size-12 object-contain">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Favicon</label>
                                <p class="mt-0.5 text-xs text-gray-500">Browser tab icon (.ico or .png).</p>
                                <input type="file" @change="platformForm.favicon = $event.target.files[0]" accept=".ico,.png,.svg" class="mt-2 block w-full text-sm text-gray-600">
                                <img x-show="platformSettings.favicon_url" :src="platformSettings.favicon_url" alt="" class="mt-2 size-8 object-contain">
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @if ($branch && $settings)
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Branch details</h2>
                    <p class="mt-1 text-xs text-gray-500">Optional label for this branch. Logos are managed in Library branding above.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Branch name</label>
                        <input type="text" x-model="form.display_name" placeholder="Main Library Center" class="mt-1 block w-full max-w-md rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                </div>
            </section>

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

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Student contact rules</h2>
                    <p class="mt-1 text-xs text-gray-500">Control whether phone or email is required when adding students. Linked siblings can share one family contact.</p>
                </div>
                <div class="space-y-4 p-5">
                    <label class="inline-flex items-start gap-3 text-sm text-gray-700">
                        <input type="checkbox" x-model="form.require_student_contact" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>
                            <span class="font-medium text-gray-900">Require student contact</span>
                            <span class="mt-1 block text-xs text-gray-500">When enabled, the first student in a family must have at least a phone or email. Linked siblings can skip contact fields.</span>
                        </span>
                    </label>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Plan expiry emails</h2>
                    <p class="mt-1 text-xs text-gray-500">A reminder email is sent in the morning, this many days before a plan ends.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div class="max-w-xs">
                        <label class="block text-sm font-medium text-gray-700">Send reminder days before expiry</label>
                        <input type="number" min="1" max="90" x-model.number="form.expiry_reminder_days" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <p class="text-xs text-gray-500">Students must have an email on their profile. Each booking receives one reminder per expiry cycle.</p>
                </div>
            </section>
            @endif

            @if ($isPlatformAdmin)
            <section class="overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm">
                <div class="border-b border-violet-100 bg-violet-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Addons</h2>
                    <p class="mt-1 text-xs text-gray-600">Install optional modules like Attendance. Installed addons appear in the sidebar.</p>
                </div>
                <div class="divide-y divide-gray-100 p-5">
                    <template x-for="addon in addonCatalog" :key="addon.slug">
                        <div class="flex flex-wrap items-start justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900" x-text="addon.name"></p>
                                <p class="mt-1 text-sm text-gray-600" x-text="addon.description"></p>
                                <p class="mt-2 text-xs text-gray-500">
                                    <span x-text="`v${addon.version}`"></span>
                                    <span x-show="addon.installed"> · Installed <span x-text="addon.installed_version ? `(v${addon.installed_version})` : ''"></span></span>
                                    <span x-show="addon.enabled" class="font-semibold text-emerald-600"> · Enabled</span>
                                    <span x-show="addon.installed && ! addon.enabled" class="font-semibold text-amber-600"> · Disabled</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" x-show="! addon.installed" @click="installAddon(addon.slug)" :disabled="addonBusy" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Install</button>
                                <button type="button" x-show="addon.installed && ! addon.enabled" @click="enableAddon(addon.slug)" :disabled="addonBusy" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 disabled:opacity-50">Enable</button>
                                <button type="button" x-show="addon.installed && addon.enabled" @click="disableAddon(addon.slug)" :disabled="addonBusy" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100 disabled:opacity-50">Disable</button>
                                <a x-show="addon.installed && addon.enabled && addon.settings_url" :href="addon.settings_url" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Open</a>
                                <button type="button" x-show="addon.installed" @click="uninstallAddon(addon.slug)" :disabled="addonBusy" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50">Uninstall</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="addonCatalog.length === 0" class="text-sm text-gray-500">No addons available.</p>
                </div>
            </section>
            @endif

            @if ($isDeveloperAdmin)
            <section class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Developer tools</h2>
                    <p class="mt-1 text-xs text-gray-600">Clear stale cache on the live server.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            @click="clearApplicationCache()"
                            :disabled="clearingCache"
                            class="inline-flex h-10 items-center rounded-lg bg-slate-800 px-5 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-50"
                            x-text="clearingCache ? 'Clearing cache...' : 'Clear application cache'"
                        ></button>
                        @if ($deploymentsUrl)
                            <a
                                href="{{ $deploymentsUrl }}"
                                class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                            >
                                Deployments &amp; domains
                            </a>
                        @endif
                    </div>
                </div>
            </section>
            @endif

            <div class="flex justify-end">
                <button type="submit" :disabled="saving" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save Settings'"></button>
            </div>
        </form>

        @if ($isPlatformAdmin)
        <section x-show="settingsTab === 'database'" x-cloak class="mt-4 space-y-6">
            <div class="rounded-xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900">
                <p class="font-semibold">How client updates work</p>
                <p class="mt-2 text-sky-800">Laravel migrations update the database structure without deleting your existing rows. The normal flow is:</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-sky-800">
                    <li>Create a backup (safety copy)</li>
                    <li>Upload the new app code to the server</li>
                    <li>Run migrations to apply schema changes</li>
                </ol>
                <p class="mt-2 text-sky-800">You do <strong>not</strong> need to import old data after a successful migration. Use restore only if something went wrong.</p>
            </div>

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
    </div>
</x-admin-layout>
