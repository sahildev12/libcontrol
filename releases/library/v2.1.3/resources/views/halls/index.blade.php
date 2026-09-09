<x-admin-layout>
    <div
        x-data="hallTable({
            rows: @js($halls),
            branches: @js($branches),
            planSnapshot: @js($planSnapshot),
            exportUrl: @js(route('halls.export')),
            storeUrl: @js(route('halls.store')),
            bulkDeleteUrl: @js(route('halls.bulk-destroy')),
            csrf: @js(csrf_token()),
            defaultBranchId: @js($defaultBranchId),
            viewingAll: @js($viewingAll ?? false),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Halls</h1>
                <p class="mt-1 text-sm text-gray-600">{{ ($viewingAll ?? false) ? 'Halls across all branches.' : 'Manage halls for '.$branchName.'.' }}</p>
            </div>
            <button type="button" @click="openCreate()" :disabled="! canAddHall()" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                Add Hall
            </button>
        </header>

        <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-950" x-show="planSnapshot">
            Plan: <span class="font-semibold" x-text="planSnapshot.limits.plan_label"></span>
            · Seats <span x-text="planSnapshot.usage.seats"></span>/<span x-text="planSnapshot.limits.max_seats ?? '∞'"></span>
            · Halls <span x-text="planSnapshot.usage.halls"></span>/<span x-text="planSnapshot.limits.max_halls ?? '∞'"></span>
        </div>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar :show-bulk-delete="true" search-placeholder="Search halls..." />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3" x-show="viewingAll">Branch</th>
                            <th class="px-4 py-3">Hall</th>
                            <th class="px-4 py-3">Capacity</th>
                            <th class="px-4 py-3">Filled</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="hall in paginatedRows()" :key="hall.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="hall.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 text-gray-600" x-show="viewingAll" x-text="hall.branch_name"></td>
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="hall.name"></td>
                                <td class="px-4 py-3" x-text="hall.seat_capacity"></td>
                                <td class="px-4 py-3" x-text="hall.filled_seats_count"></td>
                                <td class="px-4 py-3 max-w-xs truncate" x-text="hall.description || '—'"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <x-admin.icon-button tone="sky" @click="openView(hall)">View</x-admin.icon-button>
                                        <x-admin.icon-button tone="indigo" @click="openEdit(hall)">Edit</x-admin.icon-button>
                                        <x-admin.icon-button tone="red" @click="deleteOne(hall)">Delete</x-admin.icon-button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">No halls match your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        {{-- View modal --}}
        <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="viewOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Hall Details</h3>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <div><span class="font-medium text-gray-500">Branch:</span> <span x-text="viewHall?.branch_name"></span></div>
                    <div><span class="font-medium text-gray-500">Name:</span> <span x-text="viewHall?.name"></span></div>
                    <div><span class="font-medium text-gray-500">Capacity:</span> <span x-text="viewHall?.seat_capacity"></span></div>
                    <div><span class="font-medium text-gray-500">Filled:</span> <span x-text="`${viewHall?.filled_seats_count ?? 0} / ${viewHall?.seat_capacity ?? 0}`"></span></div>
                    <div><span class="font-medium text-gray-500">Description:</span> <span x-text="viewHall?.description || '—'"></span></div>
                    <div><span class="font-medium text-gray-500">Created:</span> <span x-text="viewHall?.created_at"></span></div>
                    <div><span class="font-medium text-gray-500">Updated:</span> <span x-text="viewHall?.updated_at"></span></div>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="viewOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                    <button type="button" @click="openEdit(viewHall); viewOpen = false" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Edit</button>
                </div>
            </div>
        </div>

        {{-- Form modal (create/edit) --}}
        <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="formOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="formMode === 'create' ? 'Add Hall' : 'Edit Hall'"></h3>
                </div>
                <form @submit.prevent="submitForm()" class="p-5">
                    <div x-show="viewingAll" x-cloak class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Branch</label>
                        <select x-model.number="form.branch_id" required class="admin-select mt-1 block w-full px-3 py-2" @change="onBranchChangeForHallForm()">
                            <template x-for="branch in branches" :key="branch.id">
                                <option :value="branch.id" x-text="branch.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Hall Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.name" required minlength="2" class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm" :class="formErrors.name ? 'border-red-400' : 'border-gray-300'">
                        <p class="mt-1 text-xs text-red-600" x-show="formErrors.name" x-text="formErrors.name"></p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Seat Capacity <span class="text-red-500">*</span></label>
                        <input type="number" :min="form.min_seat_capacity || 1" :max="maxSeatCapacityForForm()" x-model.number="form.seat_capacity" required class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm" :class="formErrors.seat_capacity ? 'border-red-400' : 'border-gray-300'">
                        <p class="mt-1 text-xs text-gray-500" x-show="planSnapshot">Up to <span x-text="maxSeatCapacityForForm()"></span> seats allowed on your plan for this hall.</p>
                        <p class="mt-1 text-xs text-gray-500" x-show="formMode === 'create' && !form.continue_seat_numbering">
                            Seats in this hall will be numbered starting from <span class="font-semibold">1</span>.
                        </p>
                        <p class="mt-1 text-xs text-red-600" x-show="formErrors.seat_capacity" x-text="formErrors.seat_capacity"></p>
                        <p x-show="form.min_seat_capacity > 1" class="mt-1 text-xs text-amber-700">Capacity cannot be reduced below <span x-text="form.min_seat_capacity"></span> while students are assigned.</p>
                    </div>
                    <div
                        class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4"
                        x-show="formMode === 'create' && hallsForSelectedBranch().length > 0"
                        x-cloak
                    >
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                type="checkbox"
                                x-model="form.continue_seat_numbering"
                                @change="onContinueSeatNumberingToggle()"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span>
                                <span class="block text-sm font-medium text-gray-900">Continue seat numbering from another hall</span>
                                <span class="mt-0.5 block text-xs text-gray-500">Leave unchecked to start this hall at seat 1.</span>
                            </span>
                        </label>
                        <div class="mt-3" x-show="form.continue_seat_numbering" x-cloak>
                            <label class="block text-sm font-medium text-gray-700">Continue after hall <span class="text-red-500">*</span></label>
                            <select
                                x-model.number="form.continue_from_hall_id"
                                class="admin-select mt-1 block w-full px-3 py-2"
                                :class="formErrors.continue_from_hall_id ? 'border-red-400' : ''"
                            >
                                <template x-for="hall in hallsForSelectedBranch()" :key="hall.id">
                                    <option
                                        :value="hall.id"
                                        x-text="`${hall.name} (last seat: ${hall.max_seat_number || 0})`"
                                    ></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-indigo-700" x-show="seatNumberPreview()">
                                New seats will be numbered <span class="font-semibold" x-text="seatNumberPreview()"></span>.
                            </p>
                            <p class="mt-1 text-xs text-red-600" x-show="formErrors.continue_from_hall_id" x-text="formErrors.continue_from_hall_id"></p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea x-model="form.description" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"></textarea>
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
