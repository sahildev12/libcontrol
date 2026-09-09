<x-admin-layout>
    <div
        x-data="studentTable({
            rows: @js($students),
            storeUrl: @js(route('students.store')),
            inviteStoreUrl: @js(route('students.registration-invites.store')),
            bulkDeleteUrl: @js(route('students.bulk-destroy')),
            studentSearchUrl: @js(route('students.search')),
            requireStudentContact: @js($requireStudentContact ?? false),
            csrf: @js(csrf_token()),
            branches: @js($branches ?? []),
            defaultBranchId: @js($defaultBranchId ?? null),
            viewingAll: @js($viewingAll ?? false),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Students</h1>
                <p class="mt-1 text-sm text-gray-600">Manage students{{ !empty($branchName) ? ' for '.$branchName : '' }}.</p>
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
                            <th class="px-4 py-3" x-show="viewingAll">Branch</th>
                            <th class="px-4 py-3">Phone</th>
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
                                <td class="px-4 py-3 text-gray-600" x-show="viewingAll" x-text="row.branch_name || '—'"></td>
                                <td class="px-4 py-3" x-text="row.phone"></td>
                                <td class="px-4 py-3" x-text="row.student_type_label || (row.student_type === 'trial' ? 'Trial Student' : 'Regular Student')"></td>
                                <td class="px-4 py-3 capitalize" x-text="row.status"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <x-admin.action-icon icon="view" tone="gray" @click="openView(row)" />
                                        <a
                                            :href="`/students/${row.id}/id-card`"
                                            target="_blank"
                                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition-colors hover:bg-sky-100"
                                            title="ID card"
                                            aria-label="ID card"
                                        >
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                        </a>
                                        <x-admin.action-icon icon="edit" tone="indigo" @click="openEdit(row)" />
                                        <x-admin.action-icon icon="delete" tone="red" @click="deleteOne(row)" />
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td :colspan="viewingAll ? 8 : 7" class="px-4 py-10 text-center text-gray-500">No students found.</td>
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
