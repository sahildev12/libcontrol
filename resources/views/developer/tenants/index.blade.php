<x-admin-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Client Libraries</h1>
                <p class="mt-1 text-sm text-gray-600">Each client gets their own subdomain and isolated database on {{ config('libcontrol.tenancy.base_domain') }}.</p>
            </div>
            <a href="{{ route('developer.tenants.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add client library</a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Subdomain</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Database</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Plan</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tenants as $tenant)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $tenant->client_name }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <a href="{{ $tenant->url() }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-700">{{ $tenant->host() }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $tenant->database_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ ucfirst($tenant->planTier()) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $tenant->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $tenant->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('developer.tenants.edit', $tenant) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No client libraries yet. Create one for each business subdomain.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
