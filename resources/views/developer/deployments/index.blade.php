<x-admin-layout>
    <div
        class="space-y-6"
        x-data="deploymentHubPage(@js([
            'activeTab' => $activeTab,
            'issuedLicenseKey' => session('issued_license_key'),
            'clients' => $clientOptions,
            'licenseRows' => $licenseRows,
            'openClientId' => $openClientId,
            'initialAction' => request('action'),
            'prefillDomain' => $prefillDomain,
            'prefillClientName' => $prefillClientName,
            'csrf' => csrf_token(),
            'urls' => [
                'store' => route('developer.deployments.store'),
                'authorize' => route('developer.deployments.authorize-domain'),
            ],
        ]))"
        x-init="init()"
        @authorize-domain.window="openAuthorize($event.detail)"
        @manage-client.window="openManage($event.detail)"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="max-w-3xl">
                <h1 class="text-2xl font-bold text-gray-900">Dev &amp; Domains</h1>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Authorize self-hosted LibControl installs from one place. Issue a license key, whitelist domains, and manage clients without leaving this page.
                </p>
            </div>
            <button
                type="button"
                @click="openCreate()"
                class="inline-flex shrink-0 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                Add client
            </button>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <template x-if="issuedKey">
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-4 shadow-sm">
                <p class="text-sm font-semibold text-amber-900">License key — copy as backup</p>
                <p class="mt-1 text-xs text-amber-800">This key is queued to update <code class="rounded bg-amber-100 px-1">LIBCONTROL_LICENSE_KEY</code> in the client <code class="rounded bg-amber-100 px-1">.env</code> on the next sync heartbeat. Copy it now — it will not be shown again.</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <code class="flex-1 break-all rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm font-mono text-gray-900" x-text="issuedKey"></code>
                    <button
                        type="button"
                        @click="copyIssuedKey()"
                        class="inline-flex shrink-0 items-center rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700"
                    >
                        Copy key
                    </button>
                </div>
            </div>
        </template>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-800">Unauthorized domains</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-amber-950">{{ number_format($stats['unauthorized']) }}</p>
                <p class="mt-1 text-xs text-amber-900/80">Using LibControl without a valid license</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-800">Authorized clients</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-950">{{ number_format($stats['licenses']) }}</p>
                <p class="mt-1 text-xs text-emerald-900/80">License keys you have issued</p>
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
                                            <button
                                                type="button"
                                                @click="$dispatch('authorize-domain', row)"
                                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                                            >
                                                Authorize
                                            </button>
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
            <div
                x-data="licensedDeploymentTable({ rows: @js($licenseRows) })"
                x-init="init()"
                @client-updated.window="updateRow($event.detail)"
            >
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
                                            <p class="mt-1 text-sm">Click <strong>Add client</strong> or authorize a domain from the other tab.</p>
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
                                                <button
                                                    type="button"
                                                    @click="$dispatch('manage-client', row)"
                                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                                >
                                                    Domains &amp; key
                                                </button>
                                                <a :href="row.manage_url" class="text-xs font-semibold text-gray-500 hover:text-gray-700">Remote</a>
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

        {{-- Create client modal --}}
        <div x-show="modal === 'create'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeModal()"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
                <h2 class="text-lg font-bold text-gray-900">Add authorized client</h2>
                <p class="mt-1 text-sm text-gray-600">Issue a license key and whitelist one or more domains.</p>

                <form :action="urls.store" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Client name</label>
                        <input type="text" name="client_name" x-model="createForm.client_name" required maxlength="120" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Allowed domains</label>
                        <textarea name="allowed_domains" x-model="createForm.allowed_domains" required rows="3" placeholder="aims.phenomit.com&#10;www.aims.phenomit.com" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <p class="mt-1 text-xs text-gray-500">One domain per line or comma-separated.</p>
                    </div>
                    <input type="hidden" name="grace_days" value="7">
                    <input type="hidden" name="active" value="1">
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Create &amp; issue key</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Authorize domain modal --}}
        <div x-show="modal === 'authorize'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeModal()"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
                <h2 class="text-lg font-bold text-gray-900">Authorize domain</h2>
                <p class="mt-1 text-sm text-gray-600">Whitelist this domain on an existing client or create a new one.</p>

                <form :action="urls.authorize" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Domain</label>
                        <input type="text" name="domain" x-model="authorizeForm.domain" readonly class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 text-sm text-gray-900">
                    </div>

                    <template x-if="clients.length > 0">
                        <div class="space-y-3">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                <input type="radio" value="existing" x-model="authorizeForm.mode" class="text-indigo-600 focus:ring-indigo-500">
                                Add to existing client
                            </label>
                            <div x-show="authorizeForm.mode === 'existing'">
                                <select name="deployment_id" x-model="authorizeForm.deployment_id" :disabled="authorizeForm.mode !== 'existing'" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <template x-for="client in clients" :key="client.id">
                                        <option :value="client.id" x-text="client.client_name"></option>
                                    </template>
                                </select>
                            </div>

                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                <input type="radio" value="new" x-model="authorizeForm.mode" class="text-indigo-600 focus:ring-indigo-500">
                                Create new client &amp; issue key
                            </label>
                        </div>
                    </template>

                    <div x-show="authorizeForm.mode === 'new' || clients.length === 0">
                        <label class="block text-sm font-medium text-gray-700">Client name</label>
                        <input
                            type="text"
                            name="client_name"
                            x-model="authorizeForm.client_name"
                            :required="authorizeForm.mode === 'new' || clients.length === 0"
                            :disabled="authorizeForm.mode === 'existing' && clients.length > 0"
                            maxlength="120"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Authorize</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Manage domains & license modal --}}
        <div x-show="modal === 'manage'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeModal()"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" @click.stop>
                <h2 class="text-lg font-bold text-gray-900">Domains &amp; license</h2>
                <p class="mt-1 text-sm text-gray-600">Update whitelisted domains or regenerate the license key.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Client name</label>
                        <input type="text" x-model="manageForm.client_name" maxlength="120" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Allowed domains</label>
                        <textarea x-model="manageForm.allowed_domains" rows="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <p class="mt-1 text-xs text-gray-500">One domain per line or comma-separated.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" x-model="manageForm.active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        License active
                    </label>

                    <p x-show="manageForm.error" x-text="manageForm.error" class="text-sm text-red-600"></p>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-4">
                        <form :action="manageForm.regenerate_url" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                                Regenerate license key
                            </button>
                        </form>
                        <div class="flex gap-2">
                            <button type="button" @click="closeModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Close</button>
                            <button
                                type="button"
                                @click="saveDomains()"
                                :disabled="manageForm.saving"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                            >
                                <span x-text="manageForm.saving ? 'Saving…' : 'Save domains'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
