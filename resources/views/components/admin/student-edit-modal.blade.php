{{-- Edit student modal. Expects Alpine state from studentTable: editOpen, editForm, editFormErrors, etc. --}}
<div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60" @click="closeEdit()"></div>
    <div class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex shrink-0 items-start justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Edit Student</h3>
                <p class="mt-0.5 text-sm text-gray-500" x-show="editForm.student_code" x-text="editForm.student_code"></p>
            </div>
            <button type="button" @click="closeEdit()" class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <form id="student-edit-form" @submit.prevent="submitEditForm()" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            x-model="editForm.name"
                            placeholder="e.g. Rahul Sharma"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="editFormErrors.name ? 'border-red-400 focus:border-red-500 focus:ring-red-500/30' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/30'"
                        >
                        <p x-show="editFormErrors.name" x-text="editFormErrors.name" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                        <select
                            x-model="editForm.gender"
                            class="admin-select mt-1 block w-full px-3 py-2"
                            :class="editFormErrors.gender ? 'border-red-400' : ''"
                        >
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        <p x-show="editFormErrors.gender" x-text="editFormErrors.gender" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Student Type <span class="text-red-500">*</span></label>
                        <select
                            x-model="editForm.student_type"
                            class="admin-select mt-1 block w-full px-3 py-2"
                            :class="editFormErrors.student_type ? 'border-red-400' : ''"
                        >
                            <option value="regular">Regular Student</option>
                            <option value="trial">Trial Student</option>
                        </select>
                        <p x-show="editFormErrors.student_type" x-text="editFormErrors.student_type" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                        <input
                            type="date"
                            x-model="editForm.date_of_birth"
                            :max="new Date().toISOString().slice(0, 10)"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="editFormErrors.date_of_birth ? 'border-red-400' : 'border-gray-300'"
                        >
                        <p x-show="editFormErrors.date_of_birth" x-text="editFormErrors.date_of_birth" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact <span class="text-red-500">*</span></label>
                        <input
                            type="tel"
                            x-model="editForm.phone"
                            @input="editForm.phone = sanitizeDigits($event.target.value)"
                            maxlength="10"
                            inputmode="numeric"
                            pattern="[6-9][0-9]{9}"
                            placeholder="10-digit mobile"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="editFormErrors.phone ? 'border-red-400' : 'border-gray-300'"
                        >
                        <p x-show="editFormErrors.phone" x-text="editFormErrors.phone" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            x-model="editForm.email"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="editFormErrors.email ? 'border-red-400' : 'border-gray-300'"
                        >
                        <p x-show="editFormErrors.email" x-text="editFormErrors.email" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Father's Name</label>
                        <input type="text" x-model="editForm.father_name" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Student Photo (optional)</label>
                        <input type="file" @change="editForm.photo = $event.target.files[0]" accept=".jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-600">
                        <p x-show="editForm.has_photo && ! editForm.photo" class="mt-1 text-xs text-gray-500">A photo is already on file. Upload to replace it.</p>
                        <p x-show="editFormErrors.photo" x-text="editFormErrors.photo" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea x-model="editForm.address" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"></textarea>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Document Type</label>
                        <select x-model="editForm.id_proof_type" class="admin-select mt-1 block w-full px-3 py-2" :class="editFormErrors.id_proof_type ? 'border-red-400' : ''">
                            <option value="">Select type</option>
                            <option value="Aadhaar">Aadhaar</option>
                            <option value="PAN">PAN</option>
                            <option value="Driving License">Driving License</option>
                            <option value="Voter ID">Voter ID</option>
                            <option value="Passport">Passport</option>
                            <option value="Other">Other</option>
                        </select>
                        <p x-show="editFormErrors.id_proof_type" x-text="editFormErrors.id_proof_type" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select x-model="editForm.status" class="admin-select mt-1 block w-full px-3 py-2">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">ID Document File</label>
                    <input type="file" @change="editForm.id_proof = $event.target.files[0]" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-gray-600">
                    <p x-show="editForm.has_id_proof && ! editForm.id_proof" class="mt-1 text-xs text-gray-500">An ID document is already on file. Upload to replace it.</p>
                    <p x-show="editFormErrors.id_proof" x-text="editFormErrors.id_proof" class="mt-1 text-xs text-red-600"></p>
                </div>
            </form>
        </div>

        <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 px-6 py-4">
            <button type="button" @click="closeEdit()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" form="student-edit-form" :disabled="editSaving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                <span x-show="! editSaving">Save Changes</span>
                <span x-show="editSaving">Saving...</span>
            </button>
        </div>
    </div>
</div>
