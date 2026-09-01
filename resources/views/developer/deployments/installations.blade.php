<x-admin-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Live installations</h1>
                <p class="mt-1 text-sm text-gray-600">Domains that have pinged the license server.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('developer.deployments.installations', ['filter' => 'unauthorized']) }}" class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100">Unauthorized only</a>
                <a href="{{ route('developer.deployments.installations') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">All</a>
                <a href="{{ route('developer.deployments.index') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Deployments</a>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Domain</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">App URL</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Authorized</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Hits</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">First seen</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Last seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $event->domain }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $event->app_url ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $event->is_authorized ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    @if ($event->is_authorized)
                                        Yes
                                    @elseif ($event->license_key_hash === \App\Models\LicensedDeployment::discoveryKeyHash())
                                        Discovery
                                    @else
                                        No
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $event->hit_count }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $event->first_seen_at?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $event->last_seen_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No installation pings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $events->links() }}</div>
    </div>
</x-admin-layout>
