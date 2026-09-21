<x-admin-layout>
    <div x-data="clientLibraryTable({ rows: @js($rows) })" x-init="init()">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="max-w-3xl">
                <h1 class="text-2xl font-bold text-gray-900">Client Libraries</h1>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Libraries <strong>hosted on Phenomit</strong> — each client gets a subdomain and its own database on this server.
                    For separate installs with their own server (e.g. Aims), use
                    <a href="{{ route('developer.deployments.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Dev &amp; Domains</a>.
                </p>
            </div>
            <a href="{{ route('developer.tenants.create') }}" class="inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                Add hosted library
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <section class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Total libraries</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Active</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-900">{{ number_format($stats['active']) }}</p>
            </div>
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-700">Provisioned</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-indigo-900">{{ number_format($stats['provisioned']) }}</p>
            </div>
        </section>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search client, subdomain, database..." :colspan="7" />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">Subdomain</th>
                            <th class="px-4 py-3">Database</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Provisioned</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-if="paginatedRows().length === 0">
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                    <p class="font-medium text-gray-900">No hosted libraries yet.</p>
                                    <p class="mt-1 text-sm">Create one for each business that runs on a Phenomit subdomain.</p>
                                </td>
                            </tr>
                        </template>
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="hover:bg-indigo-50/30">
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="row.client_name"></td>
                                <td class="px-4 py-3">
                                    <a :href="row.subdomain_url" target="_blank" rel="noopener" class="font-medium text-indigo-600 hover:text-indigo-800" x-text="row.subdomain"></a>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-600" x-text="row.database_name"></td>
                                <td class="px-4 py-3 text-gray-600" x-text="row.plan_label"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset" :class="row.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-gray-100 text-gray-600 ring-gray-500/20'" x-text="row.status_label"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset" :class="row.provisioned ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20' : 'bg-amber-50 text-amber-800 ring-amber-600/20'" x-text="row.provision_label"></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <template x-if="row.settings_url">
                                            <a :href="row.settings_url" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Settings</a>
                                        </template>
                                        <a :href="row.manage_url" class="text-xs font-semibold text-gray-600 hover:text-gray-800">Manage</a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer :colspan="7" />
        </section>
    </div>
</x-admin-layout>
