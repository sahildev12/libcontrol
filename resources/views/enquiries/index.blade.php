<x-admin-layout>
    <div
        x-data="enquiryTable({
            rows: @js($enquiries),
            storeUrl: @js(route('enquiries.store')),
            bulkDeleteUrl: @js(route('enquiries.bulk-destroy')),
            csrf: @js(csrf_token()),
            branches: @js($branches ?? []),
            defaultBranchId: @js($defaultBranchId ?? null),
            viewingAll: @js($viewingAll ?? false),
            statuses: @js($statuses),
            followUpStatuses: @js($followUpStatuses),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Enquiries</h1>
                <p class="mt-1 text-sm text-gray-600">Track leads, follow-ups and conversions.</p>
            </div>
            <button type="button" @click="openCreate()" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                Add Enquiry
            </button>
        </header>

        <section class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="lc-kpi-card">
                <div class="lc-kpi-card__body">
                    <div class="lc-kpi-card__label-row"><p class="lc-kpi-card__label">Total Enquiries</p></div>
                    <p class="lc-kpi-card__value" x-text="rows.length.toLocaleString()"></p>
                    <p class="lc-kpi-card__hint" x-text="`${thisMonthCount()} this month`"></p>
                </div>
                <div class="lc-kpi-card__icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z"/></svg>
                </div>
            </div>
            <div class="lc-kpi-card">
                <div class="lc-kpi-card__body">
                    <div class="lc-kpi-card__label-row"><p class="lc-kpi-card__label">New</p></div>
                    <p class="lc-kpi-card__value" x-text="statusCount('new').toLocaleString()"></p>
                    <p class="lc-kpi-card__hint">Awaiting first contact</p>
                </div>
                <div class="lc-kpi-card__icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
            </div>
            <div class="lc-kpi-card">
                <div class="lc-kpi-card__body">
                    <div class="lc-kpi-card__label-row"><p class="lc-kpi-card__label">Following up</p></div>
                    <p class="lc-kpi-card__value" x-text="(statusCount('contacted') + statusCount('follow_up')).toLocaleString()"></p>
                    <p class="lc-kpi-card__hint">Contacted &amp; follow-ups</p>
                </div>
                <div class="lc-kpi-card__icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
            </div>
            <div class="lc-kpi-card">
                <div class="lc-kpi-card__body">
                    <div class="lc-kpi-card__label-row"><p class="lc-kpi-card__label">Converted</p></div>
                    <p class="lc-kpi-card__value" x-text="statusCount('converted').toLocaleString()"></p>
                    <p class="lc-kpi-card__hint" x-text="`${conversionRate()}% conversion rate`"></p>
                </div>
                <div class="lc-kpi-card__icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </section>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center gap-1.5 border-b border-gray-200 px-4 py-3">
                <button
                    type="button"
                    @click="statusFilter = 'all'"
                    :class="statusFilter === 'all' ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50'"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
                >
                    All <span class="opacity-70" x-text="rows.length"></span>
                </button>
                <template x-for="(label, key) in statuses" :key="key">
                    <button
                        type="button"
                        @click="statusFilter = key"
                        :class="statusFilter === key ? 'bg-gray-900 text-white ring-gray-900' : statusTagClass(key) + ' hover:brightness-95'"
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
                    >
                        <span x-text="label"></span>
                        <span class="opacity-70" x-text="statusCount(key)"></span>
                    </button>
                </template>
            </div>

            <x-admin.data-table-toolbar search-placeholder="Search enquiries..." :show-bulk-delete="true" :show-export="false" />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Follow-up</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="row.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900" x-text="row.name"></span>
                                    <span x-show="row.referred_by" x-cloak class="mt-0.5 block text-[11px] font-medium text-emerald-700" x-text="`Referred by ${row.referred_by}`"></span>
                                </td>
                                <td class="px-4 py-3" x-text="row.phone"></td>
                                <td class="px-4 py-3" x-text="row.email || '—'"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset" :class="statusTagClass(row.status)" x-text="row.status_label"></span>
                                    <span x-show="row.student_code" x-cloak class="mt-0.5 block text-[11px] text-gray-500" x-text="`Student ${row.student_code}`"></span>
                                </td>
                                <td class="max-w-[240px] px-4 py-3">
                                    <template x-if="row.follow_up_date || row.follow_up_note">
                                        <div>
                                            <span x-show="row.follow_up_date" class="block text-xs font-semibold text-gray-800" x-text="row.follow_up_date_label"></span>
                                            <span x-show="row.follow_up_note" class="block truncate text-xs text-gray-500" :title="row.follow_up_note" x-text="row.follow_up_note"></span>
                                        </div>
                                    </template>
                                    <span x-show="!row.follow_up_date && !row.follow_up_note" class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-1.5">
                                        <button type="button" @click="openEdit(row)" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Edit</button>
                                        <button type="button" x-show="row.status !== 'converted'" @click="convert(row)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Convert</button>
                                        <button type="button" @click="deleteOne(row)" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">No enquiries yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="formOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="formMode === 'create' ? 'Add Enquiry' : 'Edit Enquiry'"></h3>
                </div>
                <form @submit.prevent="submitForm()" class="space-y-4 p-5">
                    <div x-show="viewingAll" x-cloak>
                        <label class="block text-sm font-medium text-gray-700">Branch</label>
                        <select x-model="form.branch_id" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <template x-for="branch in (branches || [])" :key="branch.id">
                                <option :value="branch.id" x-text="branch.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" x-model="form.name" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" x-model="form.phone" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" x-model="form.email" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message</label>
                        <textarea x-model="form.message" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"></textarea>
                    </div>
                    <div x-show="formMode === 'edit'">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select x-model="form.status" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="formMode === 'edit' && isFollowUp(form.status)" x-cloak class="grid gap-4 rounded-lg border border-amber-200 bg-amber-50/60 p-3 sm:grid-cols-2">
                        <div>
                            <label for="enquiry-follow-up-date" class="block text-sm font-medium text-gray-700">Follow-up date <span class="font-normal text-gray-400">(optional)</span></label>
                            <input id="enquiry-follow-up-date" type="date" x-model="form.follow_up_date" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label for="enquiry-follow-up-note" class="block text-sm font-medium text-gray-700">Follow-up note <span class="font-normal text-gray-400">(optional)</span></label>
                            <textarea id="enquiry-follow-up-note" x-model="form.follow_up_note" rows="2" maxlength="1000" placeholder="e.g. Will visit on Saturday with parents" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="formOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
