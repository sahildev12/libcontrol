{{-- Nested student create modal. Expects Alpine state from seatMap: studentCreateOpen, studentForm, registrationInvite, etc. --}}
<div x-show="studentCreateOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60" @click="closeStudentCreate()"></div>
    <div class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex shrink-0 items-start justify-between border-b border-gray-200 px-6 py-4">
            <div class="flex items-start gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Add New Student</h3>
                </div>
            </div>
            <button type="button" @click="closeStudentCreate()" class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="rounded-lg border border-white bg-white p-2 shadow-sm">
                        <canvas x-ref="registrationQr" width="60" height="60" class="size-[60px]"></canvas>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-sky-900">Students can self-register</p>
                        <p class="mt-1 text-xs leading-5 text-sky-800">Valid for 2 hours and accepts only one submission.</p>
                        <!-- <p class="mt-2 truncate text-xs text-sky-700" x-text="registrationInvite?.url || 'Generating link...'"></p> -->
                        <p class="mt-1 text-[11px] text-sky-700" x-show="registrationInvite?.expires_label" x-text="`Expires: ${registrationInvite?.expires_label}`"></p>
                    </div>
                    <button
                        type="button"
                        @click="copyRegistrationLink()"
                        :disabled="! registrationInvite?.url"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-sky-300 bg-white px-3 py-2 text-xs font-semibold text-sky-800 hover:bg-sky-100 disabled:opacity-50"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy Link
                    </button>
                </div>
            </div>

            <form id="seat-student-create-form" @submit.prevent="submitNewStudent()" class="mt-5 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            x-model="studentForm.name"
                            placeholder="e.g. Rahul Sharma"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="studentFormErrors.name ? 'border-red-400 focus:border-red-500 focus:ring-red-500/30' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/30'"
                        >
                        <p x-show="studentFormErrors.name" x-text="studentFormErrors.name" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                        <select
                            x-model="studentForm.gender"
                            class="admin-select mt-1 block w-full px-3 py-2"
                            :class="studentFormErrors.gender ? 'border-red-400' : ''"
                        >
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        <p x-show="studentFormErrors.gender" x-text="studentFormErrors.gender" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Student Type <span class="text-red-500">*</span></label>
                        <select
                            x-model="studentForm.student_type"
                            class="admin-select mt-1 block w-full px-3 py-2"
                            :class="studentFormErrors.student_type ? 'border-red-400' : ''"
                        >
                            <option value="regular">Regular Student</option>
                            <option value="trial">Trial Student</option>
                        </select>
                        <p x-show="studentFormErrors.student_type" x-text="studentFormErrors.student_type" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                        <input
                            type="date"
                            x-model="studentForm.date_of_birth"
                            :max="new Date().toISOString().slice(0, 10)"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="studentFormErrors.date_of_birth ? 'border-red-400' : 'border-gray-300'"
                        >
                        <p x-show="studentFormErrors.date_of_birth" x-text="studentFormErrors.date_of_birth" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact <span class="text-red-500">*</span></label>
                        <input
                            type="tel"
                            x-model="studentForm.phone"
                            @input="studentForm.phone = sanitizeDigits($event.target.value)"
                            maxlength="10"
                            inputmode="numeric"
                            pattern="[6-9][0-9]{9}"
                            placeholder="10-digit mobile"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="studentFormErrors.phone ? 'border-red-400' : 'border-gray-300'"
                        >
                        <p x-show="studentFormErrors.phone" x-text="studentFormErrors.phone" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            x-model="studentForm.email"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-sm"
                            :class="studentFormErrors.email ? 'border-red-400' : 'border-gray-300'"
                        >
                        <p x-show="studentFormErrors.email" x-text="studentFormErrors.email" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Father's Name</label>
                        <input type="text" x-model="studentForm.father_name" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Student Photo (optional)</label>
                        <input type="file" @change="studentForm.photo = $event.target.files[0]" accept=".jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-600">
                        <p x-show="studentFormErrors.photo" x-text="studentFormErrors.photo" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea x-model="studentForm.address" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"></textarea>
                    </div>
                </div>



                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Document Type</label>
                        <select x-model="studentForm.id_proof_type" class="admin-select mt-1 block w-full px-3 py-2" :class="studentFormErrors.id_proof_type ? 'border-red-400' : ''">
                            <option value="">Select type</option>
                            <option value="Aadhaar">Aadhaar</option>
                            <option value="PAN">PAN</option>
                            <option value="Driving License">Driving License</option>
                            <option value="Voter ID">Voter ID</option>
                            <option value="Passport">Passport</option>
                            <option value="Other">Other</option>
                        </select>
                        <p x-show="studentFormErrors.id_proof_type" x-text="studentFormErrors.id_proof_type" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Document File</label>
                        <input type="file" @change="studentForm.id_proof = $event.target.files[0]" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-gray-600">
                        <p x-show="studentFormErrors.id_proof" x-text="studentFormErrors.id_proof" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 px-6 py-4">
            <button type="button" @click="closeStudentCreate()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" form="seat-student-create-form" :disabled="studentSaving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                <span x-show="! studentSaving">Add Student</span>
                <span x-show="studentSaving">Saving...</span>
            </button>
        </div>
    </div>
</div>
