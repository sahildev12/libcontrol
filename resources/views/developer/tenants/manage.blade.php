<x-admin-layout>
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Remote manage — {{ $tenant->client_name }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Hosted tenant at
                    <a href="{{ $tenant->url() }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-700">{{ $tenant->host() }}</a>
                    · Database: {{ $tenant->database_name }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('developer.tenants.edit', $tenant) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit details</a>
                <a href="{{ route('developer.tenants.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
            <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Subscription & limits</h2>
                <p class="mt-1 text-xs text-amber-800">Applied immediately on the tenant database.</p>
            </div>
            <form method="POST" action="{{ route('developer.tenants.manage.plan', $tenant) }}" class="grid gap-4 p-5 md:grid-cols-2">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Plan tier</label>
                    <select name="plan_tier" class="admin-select mt-1 block w-full px-3 py-2">
                        @foreach ($planTiers as $tier)
                            <option value="{{ $tier }}" @selected(old('plan_tier', $tenant->plan_tier ?: $planSnapshot['plan_tier']) === $tier)>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Custom seat limit</label>
                    <input type="number" min="1" name="max_seats_override" value="{{ old('max_seats_override', $tenant->max_seats_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Custom hall limit</label>
                    <input type="number" min="1" name="max_halls_override" value="{{ old('max_halls_override', $tenant->max_halls_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Custom branch limit</label>
                    <input type="number" min="1" name="max_branches_override" value="{{ old('max_branches_override', $tenant->max_branches_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save plan</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Addons</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($availableAddons as $addon)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $addon['name'] ?? $addon['slug'] }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $addon['slug'] }}
                                @if ($addon['installed'] ?? false)
                                    · {{ ($addon['enabled'] ?? false) ? 'Enabled' : 'Installed' }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('developer.tenants.manage.action', $tenant) }}">
                                @csrf
                                <input type="hidden" name="action" value="addon_install">
                                <input type="hidden" name="slug" value="{{ $addon['slug'] }}">
                                <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Install</button>
                            </form>
                            <form method="POST" action="{{ route('developer.tenants.manage.action', $tenant) }}">
                                @csrf
                                <input type="hidden" name="action" value="addon_enable">
                                <input type="hidden" name="slug" value="{{ $addon['slug'] }}">
                                <button type="submit" class="rounded-lg border border-green-300 bg-green-50 px-3 py-1.5 text-xs font-medium text-green-800 hover:bg-green-100">Enable</button>
                            </form>
                            <form method="POST" action="{{ route('developer.tenants.manage.action', $tenant) }}">
                                @csrf
                                <input type="hidden" name="action" value="addon_disable">
                                <input type="hidden" name="slug" value="{{ $addon['slug'] }}">
                                <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100">Disable</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500">No addons in the catalog.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Developer tools</h2>
            </div>
            <div class="flex flex-wrap gap-2 p-5">
                <form method="POST" action="{{ route('developer.tenants.manage.action', $tenant) }}">
                    @csrf
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Clear cache</button>
                </form>
                <form method="POST" action="{{ route('developer.tenants.manage.action', $tenant) }}">
                    @csrf
                    <input type="hidden" name="action" value="database_backup">
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Database backup</button>
                </form>
                <form method="POST" action="{{ route('developer.tenants.manage.action', $tenant) }}">
                    @csrf
                    <input type="hidden" name="action" value="database_migrate">
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Run migrations</button>
                </form>
            </div>
        </section>

        @if (count($backups) > 0)
            <section class="overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">
                <div class="border-b border-red-100 bg-red-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Restore database</h2>
                    <p class="mt-1 text-xs text-red-800">Destructive — replaces the tenant database with a backup.</p>
                </div>
                <form method="POST" action="{{ route('developer.tenants.manage.action', $tenant) }}" class="space-y-4 p-5" onsubmit="return confirm('Restore this database? All current data will be replaced.');">
                    @csrf
                    <input type="hidden" name="action" value="database_restore">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Backup file</label>
                            <select name="filename" required class="admin-select mt-1 block w-full px-3 py-2">
                                @foreach ($backups as $backup)
                                    <option value="{{ $backup['filename'] }}">{{ $backup['filename'] }} ({{ $backup['created_at'] ?? '' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type RESTORE to confirm</label>
                            <input type="text" name="confirmation" required placeholder="RESTORE" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">Restore database</button>
                </form>
            </section>
        @endif
    </div>
</x-admin-layout>
