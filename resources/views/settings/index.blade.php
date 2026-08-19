<x-admin-layout>
    <div
        x-data="settingsPage({
            settings: @js($settings),
            platformSettings: @js([
                'student_code_prefix' => $platformSettings->student_code_prefix,
                'student_code_padding' => $platformSettings->student_code_padding ?: config('libspace.defaults.student_code_padding'),
                'sample_student_code' => app(\App\Services\StudentCodeService::class)->preview(),
            ]),
            updateUrl: @js(route('settings.update')),
            platformUpdateUrl: @js(route('settings.platform.update')),
            isPlatformAdmin: @js($isPlatformAdmin),
            timezone: @js(config('libspace.timezone')),
        })"
        x-init="init()"
    >
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="mt-1 text-sm text-gray-600">
                @if ($isPlatformAdmin)
                    Global student codes and branch branding for {{ $branch->display_name ?: $branch->name }}.
                @else
                    Branding and expiry email reminders for {{ $branch->display_name ?: $branch->name }}.
                @endif
            </p>
        </header>

        <form @submit.prevent="saveSettings()" class="mt-4 space-y-6">
            @if ($isPlatformAdmin)
                <section class="overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">
                    <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Global student code format</h2>
                        <p class="mt-1 text-xs text-gray-600">One prefix for the entire platform. All branches use the same student code series.</p>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Code prefix <span class="text-red-500">*</span></label>
                            <input type="text" x-model="platformForm.student_code_prefix" required pattern="[A-Za-z0-9_-]+" maxlength="20" placeholder="LIB" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm uppercase">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Number padding <span class="text-red-500">*</span></label>
                            <input type="number" min="1" max="6" x-model.number="platformForm.student_code_padding" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                            Next student code preview: <span class="font-bold" x-text="previewCode()"></span>
                        </div>
                    </div>
                </section>
            @endif

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Frontend branding</h2>
                    <p class="mt-1 text-xs text-gray-500">Shown in the sidebar, page title, and emails for the active branch.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Library display name</label>
                        <input type="text" x-model="form.display_name" placeholder="Main Library Center" class="mt-1 block w-full max-w-md rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Logo with text</label>
                            <input type="file" @change="form.logo_with_text = $event.target.files[0]" accept=".jpg,.jpeg,.png,.svg,.webp" class="mt-1 block w-full text-sm text-gray-600">
                            <img x-show="settings.logo_with_text_url" :src="settings.logo_with_text_url" alt="" class="mt-2 h-12 max-w-full object-contain">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Simple logo (icon)</label>
                            <input type="file" @change="form.simple_logo = $event.target.files[0]" accept=".jpg,.jpeg,.png,.svg,.webp" class="mt-1 block w-full text-sm text-gray-600">
                            <img x-show="settings.simple_logo_url" :src="settings.simple_logo_url" alt="" class="mt-2 size-12 object-contain">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Favicon</label>
                            <input type="file" @change="form.favicon = $event.target.files[0]" accept=".ico,.png,.svg" class="mt-1 block w-full text-sm text-gray-600">
                            <img x-show="settings.favicon_url" :src="settings.favicon_url" alt="" class="mt-2 size-8 object-contain">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Default static files can also live in <code class="rounded bg-gray-100 px-1">public/brand/</code>. Uploads here override them for your branch.</p>
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
                        <ul class="mt-2 space-y-1 text-xs">
                            <template x-for="option in settings.time_slot_options || []" :key="option.value">
                                <li x-text="option.label"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Plan expiry emails</h2>
                    <p class="mt-1 text-xs text-gray-500">Cron runs daily at 9:00 AM <span x-text="timezone"></span>.</p>
                </div>
                <div class="space-y-4 p-5">
                    <div class="max-w-xs">
                        <label class="block text-sm font-medium text-gray-700">Send reminder days before expiry</label>
                        <input type="number" min="1" max="90" x-model.number="form.expiry_reminder_days" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <p class="text-xs text-gray-500">Students must have an email on their profile. Each booking receives one reminder per expiry cycle.</p>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" :disabled="saving" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save Settings'"></button>
            </div>
        </form>
    </div>
</x-admin-layout>
