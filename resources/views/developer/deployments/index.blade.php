<x-admin-layout>
    <div
        class="space-y-6"
        x-data="{ tab: @js($activeTab) }"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="max-w-3xl">
                <h1 class="text-2xl font-bold text-gray-900">Dev &amp; Domains</h1>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Separate LibControl installs (e.g. <code class="rounded bg-gray-100 px-1">aims.phenomit.com</code>) that connect to this license server.
                    Unauthorized domains appear when someone loads LibControl without a valid license. Authorize them by creating a license and whitelisting their domain.
                </p>
            </div>
            <a href="{{ route('developer.deployments.create') }}" class="inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                Add authorized client
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-800">Unauthorized domains</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-amber-950">{{ number_format($stats['unauthorized']) }}</p>
                <p class="mt-1 text-xs text-amber-900/80">Using LibControl without a valid license</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-800">Authorized licenses</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-950">{{ number_format($stats['licenses']) }}</p>
                <p class="mt-1 text-xs text-emerald-900/80">Clients you issued a license key to</p>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-800">Domains online now</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-sky-950">{{ number_format($stats['online']) }}</p>
                <p class="mt-1 text-xs text-sky-900/80">Recently synced with a valid license</p>
            </div>
        </section>

        <div class="flex flex-wrap gap-2 border-b border-gray-200">
            <button
                type="button"
                @click="tab = 'unauthorized'"
                class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                :class="tab === 'unauthorized' ? 'border-amber-500 text-amber-900' : 'border-transparent text-gray-500 hover:text-gray-800'"
            >
                Unauthorized domains
                @if ($stats['unauthorized'] > 0)
                    <span class="ml-1.5 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white">{{ $stats['unauthorized'] > 99 ? '99+' : $stats['unauthorized'] }}</span>
                @endif
            </button>
            <button
                type="button"
                @click="tab = 'authorized'"
                class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors"
                :class="tab === 'authorized' ? 'border-indigo-500 text-indigo-900' : 'border-transparent text-gray-500 hover:text-gray-800'"
            >
                Authorized clients
            </button>
        </div>

        <section x-show="tab === 'unauthorized'" x-cloak>
            <div x-data="unauthorizedDomainTable({ rows: @js($unauthorizedRows) })" x-init="init()">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <x-admin.data-table-toolbar search-placeholder="Search domain, URL, reason..." :colspan="6" />

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px]">
                            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Domain</th>
                                    <th class="px-4 py-3">App URL</th>
                                    <th class="px-4 py-3">Why unauthorized</th>
                                    <th class="px-4 py-3">Pings</th>
                                    <th class="px-4 py-3">Last seen</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                                <template x-if="paginatedRows().length === 0">
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                            <p class="font-medium text-gray-900">No unauthorized domains right now.</p>
                                            <p class="mt-1 text-sm">Domains appear here when LibControl loads without a valid license key.</p>
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="row in paginatedRows()" :key="row.id">
                                    <tr class="hover:bg-amber-50/40">
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="row.domain"></td>
                                        <td class="px-4 py-3">
                                            <template x-if="row.app_url_link">
                                                <a :href="row.app_url_link" target="_blank" rel="noopener" class="text-indigo-600 hover:underline" x-text="row.app_url"></a>
                                            </template>
                                            <template x-if="! row.app_url_link">
                                                <span x-text="row.app_url"></span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600" x-text="row.reason"></td>
                                        <td class="px-4 py-3 tabular-nums text-gray-600" x-text="row.hits"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="row.last_seen"></td>
                                        <td class="px-4 py-3 text-right">
                                            <a :href="row.authorize_url" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Authorize
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <x-admin.data-table-footer :colspan="6" />
                </div>
            </div>
        </section>

        <section x-show="tab === 'authorized'" x-cloak>
            <div x-data="licensedDeploymentTable({ rows: @js($licenseRows) })" x-init="init()">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <x-admin.data-table-toolbar search-placeholder="Search client, domain..." :colspan="6" />

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px]">
                            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Client</th>
                                    <th class="px-4 py-3">Whitelisted domains</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Grace</th>
                                    <th class="px-4 py-3">Last sync</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                                <template x-if="paginatedRows().length === 0">
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                            <p class="font-medium text-gray-900">No authorized clients yet.</p>
                                            <p class="mt-1 text-sm">Click <strong>Add authorized client</strong> to issue a license key and whitelist domains.</p>
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="row in paginatedRows()" :key="row.id">
                                    <tr class="hover:bg-indigo-50/30">
                                        <td class="px-4 py-3 font-medium text-gray-900" x-text="row.client_name"></td>
                                        <td class="px-4 py-3 text-gray-600" x-text="row.domains"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset" :class="row.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-gray-100 text-gray-600 ring-gray-500/20'" x-text="row.status_label"></span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600" x-text="`${row.grace_days} days`"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="row.last_seen"></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <a :href="row.manage_url" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Manage</a>
                                                <a :href="row.edit_url" class="text-xs font-semibold text-gray-500 hover:text-gray-700">License</a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <x-admin.data-table-footer :colspan="6" />
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
