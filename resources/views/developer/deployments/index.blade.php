<x-admin-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Licensed Deployments</h1>
                <p class="mt-1 text-sm text-gray-600">Whitelist client domains and issue license keys.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('developer.deployments.installations') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Live installations</a>
                <a href="{{ route('developer.deployments.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add deployment</a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

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
