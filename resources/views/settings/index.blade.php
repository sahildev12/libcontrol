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

        <form @submit.prevent="saveSettings()" class="mt-4 space-y-6">
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
    </div>
</x-admin-layout>
