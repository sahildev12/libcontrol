<x-admin-layout>
    <div
        x-data="supportTicketTable({ rows: @js($rows) })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="lc-page-title">Support Tickets</h1>
                    @if ($stats['unread'] > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-800 ring-1 ring-orange-200">
                            <span class="size-2 rounded-full bg-orange-500"></span>
                            {{ number_format($stats['unread']) }} new
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-600">Tickets submitted from client LibControl installations.</p>
            </div>
        </header>

        <section class="lc-dash-stats mt-4">
            @foreach ([
                ['Total tickets', $stats['total'], 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                ['Open', $stats['open'], 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['In progress', $stats['in_progress'], 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['Resolved', $stats['resolved'], 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ] as [$label, $value, $icon])
                <article class="lc-dash-stat">
                    <div class="lc-dash-stat__head">
                        <p class="lc-dash-stat__label">{{ $label }}</p>
                        <span class="lc-dash-stat__icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                        </span>
                    </div>
                    <p class="lc-dash-stat__value">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search library, subject, email..." />

            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status</span>
                <button type="button" @click="setStatusFilter('')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="! statusFilter ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">All</button>
                <button type="button" @click="setStatusFilter('open')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="statusFilter === 'open' ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">Open</button>
                <button type="button" @click="setStatusFilter('in_progress')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="statusFilter === 'in_progress' ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">In progress</button>
                <button type="button" @click="setStatusFilter('resolved')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="statusFilter === 'resolved' ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">Resolved</button>
                <button type="button" @click="setStatusFilter('closed')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="statusFilter === 'closed' ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">Closed</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Library</th>
                            <th class="px-4 py-3">Subject</th>
                            <th class="px-4 py-3">Reporter</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Priority</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="transition-colors hover:bg-indigo-50/30" :class="row.unread ? 'bg-orange-50/50' : ''">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            :class="row.unread ? 'bg-orange-500 ring-2 ring-orange-200' : 'bg-transparent'"
                                            :title="row.unread ? 'New — not opened yet' : ''"
                                        ></span>
                                        <span class="font-medium text-gray-500" x-text="`#${row.id}`"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900" x-text="row.library_name"></p>
                                    <p class="mt-0.5 text-xs text-gray-500" x-text="row.library_sub"></p>
                                </td>
                                <td class="px-4 py-3">
                                    <a
                                        :href="row.show_url"
                                        class="font-medium hover:underline"
                                        :class="row.unread ? 'text-gray-900' : 'text-indigo-600'"
                                        x-text="row.subject"
                                    ></a>
                                    <p class="mt-0.5 text-xs text-gray-500" x-text="row.category"></p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900" x-text="row.reporter_name"></p>
                                    <button
                                        type="button"
                                        @click="openEmail(row.reporter_email)"
                                        class="mt-0.5 max-w-[14rem] truncate text-left text-xs text-gray-500 hover:text-indigo-600 hover:underline"
                                        :title="row.reporter_email"
                                        x-text="row.reporter_email"
                                    ></button>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                        :class="statusBadgeClass(row.status)"
                                        x-text="row.status_label"
                                    ></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-gray-700">
                                        <span class="size-2 rounded-full" :class="priorityDotClass(row.priority)"></span>
                                        <span x-text="row.priority_label"></span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600" x-text="row.created_at"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <x-admin.icon-button tone="gray" title="View reporter email" @click="openEmail(row.reporter_email)">
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </x-admin.icon-button>
                                        <a
                                            :href="row.show_url"
                                            title="View ticket"
                                            class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-100"
                                        >
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="8" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">No support tickets found.</p>
                                <p class="mt-1 text-sm text-gray-500" x-show="search || statusFilter">Try adjusting your search or filters.</p>
                                <p class="mt-1 text-sm text-gray-500" x-show="! search && ! statusFilter">Tickets from client installations will appear here.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer :colspan="8" />
        </section>

        <div
            x-show="emailModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="closeEmail()"
        >
            <div class="absolute inset-0 bg-gray-900/40" @click="closeEmail()"></div>
            <div class="relative z-10 w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl" @click.stop>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Reporter email</h3>
                        <p class="mt-1 text-xs text-gray-500">Full email address for this ticket reporter.</p>
                    </div>
                    <button type="button" @click="closeEmail()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p class="mt-4 break-all rounded-lg bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-800" x-text="emailModal"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="closeEmail()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Close
                    </button>
                    <a
                        :href="emailModal ? `mailto:${emailModal}` : '#'"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Send email
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
