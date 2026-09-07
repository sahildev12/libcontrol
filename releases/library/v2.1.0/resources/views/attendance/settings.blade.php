<x-admin-layout>
    <div
        x-data="attendanceSettingsPanel({
            settings: @js($settings),
            checkInUrl: @js($checkInUrl),
            branchId: @js($branch->id),
            updateUrl: @js(route('attendance.settings.update')),
            rotateUrl: @js(route('attendance.settings.rotate-qr')),
            branches: @js($branches),
            viewingAll: @js($viewingAll),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Attendance Setup</h1>
                <p class="mt-1 text-sm text-gray-500">Configure QR check-in and staff GPS geofence for {{ $branch->name }}.</p>
            </div>
            <a href="{{ route('attendance.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Back to register
            </a>
        </header>

        @if ($viewingAll && $branches->isNotEmpty())
            <form method="get" action="{{ route('attendance.settings') }}" class="mt-4 flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Branch</label>
                <select name="branch_id" onchange="this.form.submit()" class="admin-select rounded-lg border-gray-200 text-sm">
                    @foreach ($branches as $item)
                        <option value="{{ $item->id }}" @selected($item->id === $branch->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Check-in methods</h2>
                <div class="mt-4 space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" x-model="form.student_qr_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Student QR (mobile web)</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" x-model="form.staff_gps_enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Staff GPS (Android app)</span>
                    </label>
                </div>

                <h3 class="mt-6 text-sm font-semibold text-gray-900">Geofence</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Latitude</label>
                        <input type="number" step="any" x-model="form.geofence_latitude" class="w-full rounded-lg border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Longitude</label>
                        <input type="number" step="any" x-model="form.geofence_longitude" class="w-full rounded-lg border-gray-200 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Radius (meters)</label>
                        <input type="number" min="25" max="5000" x-model="form.geofence_radius_meters" class="w-full rounded-lg border-gray-200 text-sm">
                    </div>
                </div>

                <button type="button" @click="save()" :disabled="saving" class="mt-5 inline-flex h-10 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                    Save settings
                </button>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Branch QR poster</h2>
                <p class="mt-2 text-sm text-gray-600">Students scan this link on their phone to check in with student code + phone number.</p>

                <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                    <p class="break-all text-xs font-mono text-gray-700" x-text="checkInUrl">{{ $checkInUrl }}</p>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a :href="qrImageUrl" target="_blank" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        Open QR image
                    </a>
                    <button type="button" @click="copyUrl()" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        Copy link
                    </button>
                    <button type="button" @click="rotateQr()" :disabled="saving" class="inline-flex h-10 items-center rounded-lg border border-amber-200 bg-amber-50 px-3.5 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                        Regenerate QR
                    </button>
                </div>

                <div class="mt-6 flex justify-center">
                    <img :src="qrImageUrl" alt="Attendance QR code" class="h-48 w-48 rounded-lg border border-gray-200 bg-white p-2">
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
