<x-admin-layout>
    <div
        x-data="notificationTable({
            rows: @js($alerts),
            markReadUrl: @js(route('notifications.mark-read')),
            markAllUrl: @js(route('notifications.mark-all-read')),
            bulkDeleteUrl: @js(route('notifications.bulk-destroy')),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                <p class="mt-1 text-sm text-gray-600">Plans ending soon and new enquiries.</p>
            </div>
            <button
                type="button"
                x-show="unreadCount() > 0"
                @click="markAllRead()"
                class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Mark all as read
            </button>
        </header>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search notifications..." :show-bulk-delete="true" />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Message</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="cursor-pointer hover:bg-indigo-50/40" :class="row.unread ? 'bg-amber-50/40' : ''" @click="openView(row)">
                                <td class="px-4 py-3" @click.stop>
                                    <input type="checkbox" :value="row.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span x-show="row.unread" class="size-2 shrink-0 rounded-full bg-amber-500" title="Not viewed yet"></span>
                                        <span x-show="! row.unread" class="size-2 shrink-0 rounded-full bg-transparent"></span>
                                        <span class="font-medium text-gray-900" x-text="row.title"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3" x-text="row.message"></td>
                                <td class="px-4 py-3" x-text="row.type_label || row.type"></td>
                                <td class="px-4 py-3" x-text="row.date"></td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <x-admin.icon-button tone="gray" @click="openView(row)">View</x-admin.icon-button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">No alerts right now.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeView()"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="viewRow?.title || 'Notification'"></h3>
                    <button type="button" @click="closeView()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 px-5 py-4 text-sm">
                    <p class="text-gray-700" x-text="viewRow?.message"></p>
                    <template x-for="item in (viewRow?.details || [])" :key="item.label">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400" x-text="item.label"></p>
                            <p class="mt-0.5 text-gray-900" x-text="item.value"></p>
                        </div>
                    </template>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3">
                    <button type="button" @click="closeView()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                    <a :href="viewRow?.url" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" x-text="viewRow?.action_label || 'Open'"></a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
