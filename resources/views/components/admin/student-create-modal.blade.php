{{-- Nested student create modal. Expects Alpine state from seatMap / studentTable. --}}
<div x-show="studentCreateOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60" @click="closeStudentCreate()"></div>
    <div class="relative flex max-h-[92vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-3">
            <h3 class="text-base font-bold text-gray-900">Add Student</h3>
            <button type="button" @click="closeStudentCreate()" class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
            <div class="mb-4 flex items-center gap-3 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2">
                <button
                    type="button"
                    @click="openRegistrationQrPreview()"
                    class="shrink-0 rounded bg-white p-0.5 ring-1 ring-sky-200 transition hover:ring-sky-400"
                    title="Click to enlarge QR"
                >
                    <canvas x-ref="registrationQr" width="48" height="48" class="size-12 cursor-pointer rounded"></canvas>
                </button>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-sky-900">Self-register link</p>
                    <p class="text-[11px] text-sky-700" x-show="registrationInvite?.expires_label" x-text="`Expires ${registrationInvite?.expires_label}`"></p>
                    <p class="text-[11px] text-sky-600">Click QR to enlarge</p>
                </div>
                <button type="button" @click="copyRegistrationLink()" :disabled="! registrationInvite?.url" class="shrink-0 rounded-md border border-sky-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-sky-800 hover:bg-sky-100 disabled:opacity-50">Copy</button>
            </div>

            <form id="seat-student-create-form" @submit.prevent="submitNewStudent()" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="studentForm.name" placeholder="" class="mt-1 block w-full rounded-lg border px-3 py-1.5 text-sm" :class="studentFormErrors.name ? 'border-red-400' : 'border-gray-300'">
                    <p x-show="studentFormErrors.name" x-text="studentFormErrors.name" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                    <select x-model="studentForm.gender" class="admin-select mt-1 block w-full px-3 py-1.5 text-sm" :class="studentFormErrors.gender ? 'border-red-400' : ''">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Type <span class="text-red-500">*</span></label>
                    <select
                        x-model="studentForm.student_type"
                        class="admin-select mt-1 block w-full px-3 py-1.5 text-sm font-semibold"
                        :class="studentForm.student_type === 'trial'
                            ? 'border-sky-400 bg-sky-50 text-sky-800'
                            : 'border-emerald-400 bg-emerald-50 text-emerald-800'"
                    >
                        <option value="trial">Trial</option>
                        <option value="regular">Regular</option>
                    </select>
                </div>
                <div x-show="(branches || []).length > 1" x-cloak>
                    <label class="block text-xs font-medium text-gray-700">Branch <span class="text-red-500">*</span></label>
                    <select
                        x-model="studentForm.branch_id"
                        :disabled="Boolean(selectedSeat?.branch_id)"
                        class="admin-select mt-1 block w-full px-3 py-1.5 text-sm disabled:bg-gray-100 disabled:text-gray-600"
                        :class="studentFormErrors.branch_id ? 'border-red-400' : ''"
                    >
                        <template x-for="branch in (branches || [])" :key="branch.id">
                            <option :value="branch.id" x-text="branch.name"></option>
                        </template>
                    </select>
                    <p x-show="selectedSeat?.branch_id" class="mt-1 text-xs text-gray-500">Locked to this seat’s branch.</p>
                    <p x-show="studentFormErrors.branch_id" x-text="studentFormErrors.branch_id" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                    <input type="date" x-model="studentForm.date_of_birth" :max="new Date().toISOString().slice(0, 10)" class="mt-1 block w-full rounded-lg border px-3 py-1.5 text-sm" :class="studentFormErrors.date_of_birth ? 'border-red-400' : 'border-gray-300'">
                    <p x-show="studentFormErrors.date_of_birth" x-text="studentFormErrors.date_of_birth" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Phone <span class="text-red-500">*</span></label>
                    <input type="tel" x-model="studentForm.phone" @input="studentForm.phone = sanitizeDigits($event.target.value)" maxlength="10" inputmode="numeric" placeholder="10-digit mobile" class="mt-1 block w-full rounded-lg border px-3 py-1.5 text-sm" :class="studentFormErrors.phone ? 'border-red-400' : 'border-gray-300'">
                    <p x-show="studentFormErrors.phone" x-text="studentFormErrors.phone" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" x-model="studentForm.email" required class="mt-1 block w-full rounded-lg border px-3 py-1.5 text-sm" :class="studentFormErrors.email ? 'border-red-400' : 'border-gray-300'">
                    <p x-show="studentFormErrors.email" x-text="studentFormErrors.email" class="mt-1 text-xs text-red-600"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Father's Name</label>
                    <input type="text" x-model="studentForm.father_name" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700">Address</label>
                    <input type="text" x-model="studentForm.address" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Photo</label>
                    <input type="file" @change="studentForm.photo = $event.target.files[0]" accept=".jpg,.jpeg,.png" class="mt-1 block w-full text-xs text-gray-600">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">ID Type</label>
                    <select x-model="studentForm.id_proof_type" class="admin-select mt-1 block w-full px-3 py-1.5 text-sm">
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
                    <label class="block text-xs font-medium text-gray-700">ID File</label>
                    <input type="file" @change="studentForm.id_proof = $event.target.files[0]" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-xs text-gray-600">
                    <p x-show="studentFormErrors.id_proof" x-text="studentFormErrors.id_proof" class="mt-1 text-xs text-red-600"></p>
                </div>
            </form>
        </div>

        <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 px-5 py-3">
            <button type="button" @click="closeStudentCreate()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" form="seat-student-create-form" :disabled="studentSaving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                <span x-show="! studentSaving">Add Student</span>
                <span x-show="studentSaving">Saving...</span>
            </button>
        </div>
    </div>
</div>

<div x-show="registrationQrPreviewOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/70" @click="closeRegistrationQrPreview()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl" @click.stop>
        <div class="mb-3 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-900">Registration QR</h4>
            <button type="button" @click="closeRegistrationQrPreview()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex justify-center rounded-xl border border-gray-100 bg-white p-4">
            <canvas x-ref="registrationQrLarge" width="280" height="280" class="size-64"></canvas>
        </div>
        <p class="mt-3 break-all text-center text-xs text-gray-500" x-text="registrationInvite?.url"></p>
        <button type="button" @click="copyRegistrationLink()" class="mt-3 w-full rounded-lg bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">Copy link</button>
    </div>
</div>
