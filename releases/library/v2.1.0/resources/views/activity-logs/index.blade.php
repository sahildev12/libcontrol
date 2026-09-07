<x-admin-layout>
    <div
        x-data="activityLogTable({
            rows: @js($logs),
            isPlatformAdmin: @js($isPlatformAdmin),
            bulkDeleteUrl: @js(route('activity-logs.bulk-destroy')),
        })"
        x-init="init()"
    >
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Activity Log</h1>
            <p class="mt-1 text-sm text-gray-600">What people did in {{ $scopeLabel }}.</p>
        </header>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search activity..." :show-bulk-delete="true" />

            <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Show</span>
                <button type="button" @click="setActorFilter('')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="! actorFilter ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">All</button>
                <button type="button" @click="setActorFilter('admin')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="actorFilter === 'admin' ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">Admin only</button>
                <button type="button" @click="setActorFilter('branch')" class="rounded-full border px-3 py-1 text-xs font-semibold" :class="actorFilter === 'branch' ? 'border-indigo-500 bg-indigo-50 text-indigo-800' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">Library staff only</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">When</th>
                            <th class="px-4 py-3">Who</th>
                            <th class="px-4 py-3">What happened</th>
                            <th class="px-4 py-3">Library</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="row.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="row.created_at"></td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900" x-text="row.user_name"></p>
                                    <p class="text-xs text-gray-500" x-text="row.actor_label"></p>
                                </td>
                                <td class="px-4 py-3">
                                    <p x-text="row.description"></p>
                                    <p class="mt-0.5 text-xs text-gray-500" x-show="row.change_summary" x-text="row.change_summary"></p>
                                </td>
                                <td class="px-4 py-3 text-gray-600" x-text="row.branch_name"></td>
                                <td class="px-4 py-3 text-right">
                                    <x-admin.icon-button tone="gray" @click="openView(row)">View</x-admin.icon-button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">No activity recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeView()"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">What happened</h3>
                    <button type="button" @click="closeView()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-4 text-sm">
                    <p x-show="viewLoading" class="py-8 text-center text-gray-500">Loading…</p>
                    <template x-if="viewLog && ! viewLoading">
                        <div class="space-y-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">When</p>
                                <p class="mt-0.5 font-medium text-gray-900" x-text="viewLog.created_at_full || viewLog.created_at"></p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Who</p>
                                <p class="mt-0.5 font-medium text-gray-900" x-text="viewLog.user_name"></p>
                                <p class="text-xs text-gray-500" x-text="viewLog.actor_label"></p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">What they did</p>
                                <p class="mt-0.5 text-gray-800" x-text="viewLog.description"></p>
                            </div>
                            <div x-show="(viewLog.changes || []).length">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">What changed</p>
                                <ul class="mt-1 space-y-1">
                                    <template x-for="change in (viewLog.changes || [])" :key="change.label">
                                        <li class="rounded-lg bg-gray-50 px-3 py-2 text-gray-800">
                                            <span class="font-medium" x-text="change.label"></span>
                                            <span class="text-gray-500"> from </span>
                                            <span x-text="change.from"></span>
                                            <span class="text-gray-500"> to </span>
                                            <span x-text="change.to"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Library</p>
                                <p class="mt-0.5 text-gray-900" x-text="viewLog.branch_name"></p>
                            </div>
                            <template x-for="item in (viewLog.details || [])" :key="item.label">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400" x-text="item.label"></p>
                                    <p class="mt-0.5 text-gray-900" x-text="item.value"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="border-t border-gray-200 px-5 py-3 text-right">
                    <button type="button" @click="closeView()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
