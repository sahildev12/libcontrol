<x-admin-layout>
    <div
        x-data="platformBranchesPage({
            branches: @js($branches),
            planSnapshot: @js($planSnapshot),
            storeUrl: @js(route('branch.store')),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Branches</h1>
                <p class="mt-1 text-sm text-gray-600">Create and manage all library branches. Use the top bar to switch the active branch for halls, students, and settings.</p>
            </div>
            <button type="button" @click="openCreate()" :disabled="! canAddBranch()" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                Create Branch
            </button>
        </header>

        <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-950" x-show="planSnapshot">
            Plan: <span class="font-semibold" x-text="planSnapshot.limits.plan_label"></span>
            · Branches <span x-text="planSnapshot.usage.branches"></span>/<span x-text="planSnapshot.limits.max_branches ?? '∞'"></span>
        </div>

        <section
            class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            x-data="{ search: '' }"
        >
            <div class="border-b border-gray-200 px-4 py-3">
                <input type="search" x-model="search" placeholder="Search branches..." class="w-full max-w-sm rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Branch</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Halls</th>
                            <th class="px-4 py-3">Students</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="branch in filteredBranches(search)" :key="branch.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="branch.name"></td>
                                <td class="px-4 py-3">
                                    <div x-text="branch.contact_person || '—'"></div>
                                    <div class="text-xs text-gray-500" x-text="branch.phone || ''"></div>
                                </td>
                                <td class="px-4 py-3" x-text="branch.halls_count"></td>
                                <td class="px-4 py-3" x-text="branch.students_count"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <x-admin.icon-button tone="sky" @click="openView(branch)">View</x-admin.icon-button>
                                        <x-admin.icon-button tone="indigo" @click="openEdit(branch)">Edit</x-admin.icon-button>
                                        <x-admin.icon-button tone="red" @click="deleteBranch(branch)">Delete</x-admin.icon-button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredBranches(search).length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">No branches found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Create branch --}}
        <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="createOpen = false"></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Create Branch</h3>
                </div>
                <form @submit.prevent="submitCreate()" class="space-y-4 p-5" novalidate>
                    @include('branch.partials.form-fields', ['mode' => 'create'])
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="createOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-text="saving ? 'Creating...' : 'Create Branch'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit branch --}}
        <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="editOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Branch</h3>
                </div>
                <form @submit.prevent="submitEdit()" class="space-y-4 p-5" novalidate>
                    @include('branch.partials.form-fields', ['mode' => 'edit'])
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="editOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- View branch + halls --}}
        <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="viewOpen = false"></div>
            <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="viewBranch?.name"></h3>
                    <p class="mt-1 text-sm text-gray-500">Branch details and halls</p>
                </div>
                <div class="space-y-4 p-5">
                    <div class="grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm sm:grid-cols-2">
                        <div><span class="font-medium text-gray-500">Contact:</span> <span x-text="viewBranch?.contact_person || '—'"></span></div>
                        <div><span class="font-medium text-gray-500">Phone:</span> <span x-text="viewBranch?.phone || '—'"></span></div>
                        <div><span class="font-medium text-gray-500">Email:</span> <span x-text="viewBranch?.email || viewBranch?.login_email || '—'"></span></div>
                        <div class="sm:col-span-2"><span class="font-medium text-gray-500">Address:</span> <span x-text="viewBranch?.address || '—'"></span></div>
                        <div class="sm:col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-3" x-show="viewBranch?.temporary_password">
                            <p class="text-xs font-semibold text-amber-900">New password (copy now)</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <code class="rounded bg-white px-2 py-1 text-sm text-gray-900" x-text="viewBranch?.temporary_password"></code>
                                <button type="button" @click="copyTemporaryPassword()" class="rounded-lg border border-amber-300 bg-white px-2.5 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-100">Copy</button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">Halls</h4>
                        <div class="mt-2 overflow-hidden rounded-lg border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2">Hall</th>
                                        <th class="px-3 py-2">Capacity</th>
                                        <th class="px-3 py-2">Filled</th>
                                        <th class="px-3 py-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="hall in viewBranch?.halls || []" :key="hall.id">
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-900" x-text="hall.name"></td>
                                            <td class="px-3 py-2" x-text="hall.seat_capacity"></td>
                                            <td class="px-3 py-2" x-text="hall.filled_seats_count"></td>
                                            <td class="px-3 py-2 text-right">
                                                <div class="inline-flex gap-1.5">
                                                    <x-admin.icon-button tone="sky" @click="openHallView(hall)">View</x-admin.icon-button>
                                                    <x-admin.icon-button tone="indigo" @click="openHallEdit(hall)">Edit</x-admin.icon-button>
                                                    <x-admin.icon-button tone="red" @click="deleteHall(hall)">Delete</x-admin.icon-button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="!(viewBranch?.halls || []).length">
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">No halls in this branch yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="viewOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">Close</button>
                    <button type="button" @click="openPasswordReset(viewBranch)" :disabled="saving" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:opacity-50">Reset Password</button>
                    <button type="button" @click="openEdit(viewBranch); viewOpen = false" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Edit Branch</button>
                </div>
            </div>
        </div>

        {{-- Custom password reset --}}
        <div x-show="passwordResetOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="passwordResetOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl" @click.stop>
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Set branch password</h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="passwordResetBranch?.name"></p>
                </div>
                <form @submit.prevent="submitPasswordReset()" class="space-y-4 p-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custom password</label>
                        <input type="text" x-model="passwordResetForm.password" minlength="8" required autocomplete="new-password" placeholder="At least 8 characters" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <button type="button" @click="passwordResetForm.password = generatePassword()" class="mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Generate password</button>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="passwordResetOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Set Password'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Hall view --}}
        <div x-show="hallViewOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="hallViewOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Hall Details</h3>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <div><span class="font-medium text-gray-500">Name:</span> <span x-text="hallView?.name"></span></div>
                    <div><span class="font-medium text-gray-500">Capacity:</span> <span x-text="hallView?.seat_capacity"></span></div>
                    <div><span class="font-medium text-gray-500">Filled:</span> <span x-text="`${hallView?.filled_seats_count ?? 0} / ${hallView?.seat_capacity ?? 0}`"></span></div>
                    <div><span class="font-medium text-gray-500">Description:</span> <span x-text="hallView?.description || '—'"></span></div>
                    <div><span class="font-medium text-gray-500">Created:</span> <span x-text="hallView?.created_at"></span></div>
                    <div><span class="font-medium text-gray-500">Updated:</span> <span x-text="hallView?.updated_at"></span></div>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="hallViewOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">Close</button>
                    <button type="button" @click="openHallEdit(hallView); hallViewOpen = false" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Edit</button>
                </div>
            </div>
        </div>

        {{-- Hall edit --}}
        <div x-show="hallEditOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="hallEditOpen = false"></div>
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Hall</h3>
                </div>
                <form @submit.prevent="submitHallEdit()" class="space-y-4 p-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hall name</label>
                        <input type="text" x-model="hallForm.name" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Seat capacity</label>
                        <input type="number" min="1" x-model.number="hallForm.seat_capacity" :min="hallForm.min_seat_capacity || 1" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <p x-show="hallForm.min_seat_capacity > 1" class="mt-1 text-xs text-amber-700">Capacity cannot be reduced below <span x-text="hallForm.min_seat_capacity"></span> while students are assigned.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea x-model="hallForm.description" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="hallEditOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
