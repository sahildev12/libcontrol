<x-admin-layout>
    <div
        x-data="studentTable({
            rows: @js($students),
            storeUrl: @js(route('students.store')),
            inviteStoreUrl: @js(route('students.registration-invites.store')),
            bulkDeleteUrl: @js(route('students.bulk-destroy')),
            csrf: @js(csrf_token()),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Students</h1>
                <p class="mt-1 text-sm text-gray-600">Manage students for {{ Auth::user()->branch?->name }}.</p>
            </div>
            <button type="button" @click="openCreate()" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                Add Student
            </button>
        </header>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search by code, name, phone..." :show-bulk-delete="true" />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="row.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="row.student_code"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img
                                            x-show="row.photo_url"
                                            :src="row.photo_url"
                                            :alt="row.name"
                                            class="size-9 shrink-0 rounded-full border border-gray-200 object-cover"
                                        >
                                        <div
                                            x-show="! row.photo_url"
                                            class="flex size-9 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-indigo-100 text-xs font-bold text-indigo-700"
                                            x-text="row.initials || '?'"
                                        ></div>
                                        <span class="font-medium text-gray-900" x-text="row.name"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3" x-text="row.phone"></td>
                                <td class="px-4 py-3" x-text="row.email || '—'"></td>
                                <td class="px-4 py-3" x-text="row.student_type_label || (row.student_type === 'trial' ? 'Trial Student' : 'Regular Student')"></td>
                                <td class="px-4 py-3 capitalize" x-text="row.status"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <button type="button" @click="openView(row)" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">View</button>
                                        <button type="button" @click="openEdit(row)" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Edit</button>
                                        <button type="button" @click="deleteOne(row)" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">No students found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        <x-admin.student-create-modal />
        <x-admin.student-edit-modal />
        <x-admin.student-view-modal />
    </div>
</x-admin-layout>
