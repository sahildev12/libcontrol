<x-admin-layout>
    <div
        x-data="notificationTable({ rows: @js($alerts) })"
        x-init="init()"
    >
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-600">Alerts for expiring plans and new enquiries.</p>
        </header>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search notifications..." />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Message</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="row.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="row.title"></td>
                                <td class="px-4 py-3" x-text="row.message"></td>
                                <td class="px-4 py-3 capitalize" x-text="row.type?.replaceAll('_', ' ')"></td>
                                <td class="px-4 py-3" x-text="row.date"></td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">No alerts right now.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>
    </div>
</x-admin-layout>
