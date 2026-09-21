<x-admin-layout>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Portal Settings</h1>
            <p class="mt-1 text-sm text-gray-600">
                Manage settings for libraries <strong>hosted on Phenomit</strong> (one database per client, managed from this hub).
                Separate client installations such as <code class="rounded bg-gray-100 px-1">aims.phenomit.com</code> are listed under
                <a href="{{ route('developer.deployments.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Dev &amp; Domains</a>
                and are edited on that site&rsquo;s own admin panel.
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Client</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Subdomain</th>
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
                            <td class="px-4 py-3">
                                @if ($tenant->provisioned_at)
                                    <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Ready</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">Not provisioned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-3">
                                @if ($tenant->provisioned_at)
                                    <a href="{{ route('developer.portals.settings', $tenant) }}" class="font-medium text-indigo-600 hover:text-indigo-700">Manage settings</a>
                                @endif
                                <a href="{{ route('developer.tenants.manage', $tenant) }}" class="font-medium text-gray-600 hover:text-gray-700">Remote manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-500">
                                No hosted client libraries yet.
                                <span class="mt-1 block text-xs text-gray-400">
                                    Licensed separate installs (e.g. Aims) appear under
                                    <a href="{{ route('developer.deployments.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Dev &amp; Domains</a>,
                                    not here.
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
