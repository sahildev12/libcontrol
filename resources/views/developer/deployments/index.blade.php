<x-admin-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Licensed Deployments</h1>
                <p class="mt-1 text-sm text-gray-600">Whitelist client domains and issue license keys. New installs also appear automatically under Live installations.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('developer.deployments.installations') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Live installations</a>
                <a href="{{ route('developer.deployments.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add deployment</a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-indigo-100 bg-indigo-50 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Recent live installations</h2>
                    <p class="text-xs text-gray-600">Domains that loaded LibSpace and pinged this server.</p>
                </div>
                <a href="{{ route('developer.deployments.installations') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-800">View all</a>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Domain</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">App URL</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Licensed</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Last seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentInstallations as $event)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $event->domain }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $event->app_url ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $event->is_authorized ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-900' }}">
                                    {{ $event->is_authorized ? 'Yes' : ($event->license_key_hash === \App\Models\LicensedDeployment::discoveryKeyHash() ? 'Discovery' : 'No') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $event->last_seen_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No installation pings yet. Client sites appear here when any page is loaded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Domains</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Grace</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($deployments as $deployment)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $deployment->client_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ implode(', ', $deployment->allowed_domains ?? []) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $deployment->grace_days }} days</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $deployment->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $deployment->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('developer.deployments.edit', $deployment) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No deployments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
