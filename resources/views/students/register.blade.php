<x-students.registration-shell
    :branch-name="$branchName"
    heading="Student Self-Registration"
    :description="'Register for '.$branchName.'. This link expires in 2 hours and works only once.'"
    page-title="Student Registration"
>
    <form
        id="student-registration-form"
        method="POST"
        action="{{ route('students.register.store', $invite->token) }}"
        enctype="multipart/form-data"
        class="space-y-5 px-6 py-6"
        novalidate
    >
        @csrf

        <div class="lc-reg-field">
            <label class="lc-reg-label" for="reg-name">Full Name <span class="text-red-500">*</span></label>
            <input id="reg-name" type="text" name="name" value="{{ old('name') }}" required minlength="2" maxlength="255" autocomplete="name" placeholder="e.g. Rahul Sharma" class="lc-reg-input" data-validate="name">
            <p class="lc-reg-error hidden" data-client-error="name"></p>
            @error('name') <p class="lc-reg-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="lc-reg-field">
                <label class="lc-reg-label" for="reg-gender">Gender <span class="text-red-500">*</span></label>
                <select id="reg-gender" name="gender" required class="lc-reg-select" data-validate="gender">
                    <option value="male" @selected(old('gender', 'male') === 'male')>Male</option>
                    <option value="female" @selected(old('gender') === 'female')>Female</option>
                </select>
                <p class="lc-reg-error hidden" data-client-error="gender"></p>
                @error('gender') <p class="lc-reg-error">{{ $message }}</p> @enderror
            </div>
            <div class="lc-reg-field">
                <label class="lc-reg-label" for="reg-dob">Date of Birth <span class="text-red-500">*</span></label>
                <input id="reg-dob" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required max="{{ now()->subDay()->format('Y-m-d') }}" class="lc-reg-input" data-validate="date_of_birth">
                <p class="lc-reg-error hidden" data-client-error="date_of_birth"></p>
                @error('date_of_birth') <p class="lc-reg-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="lc-reg-field">
                <label class="lc-reg-label" for="reg-phone">Contact <span class="text-red-500">*</span></label>
                <input id="reg-phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="10" inputmode="numeric" autocomplete="tel" placeholder="10-digit mobile" class="lc-reg-input" data-validate="phone">
                <p class="lc-reg-error hidden" data-client-error="phone"></p>
                @error('phone') <p class="lc-reg-error">{{ $message }}</p> @enderror
            </div>
            <div class="lc-reg-field">
                <label class="lc-reg-label" for="reg-email">Email <span class="text-red-500">*</span></label>
                <input id="reg-email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="student@example.com" class="lc-reg-input" data-validate="email">
                <p class="lc-reg-error hidden" data-client-error="email"></p>
                @error('email') <p class="lc-reg-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="lc-reg-field">
            <label class="lc-reg-label" for="reg-father">Father's Name</label>
            <input id="reg-father" type="text" name="father_name" value="{{ old('father_name') }}" maxlength="255" class="lc-reg-input" data-validate="father_name">
            <p class="lc-reg-error hidden" data-client-error="father_name"></p>
        </div>

        <div class="lc-reg-field">
            <label class="lc-reg-label" for="reg-address">Address</label>
            <textarea id="reg-address" name="address" rows="2" maxlength="1000" class="lc-reg-input" data-validate="address">{{ old('address') }}</textarea>
            <p class="lc-reg-error hidden" data-client-error="address"></p>
            @error('address') <p class="lc-reg-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="lc-reg-field">
                <label class="lc-reg-label" for="reg-id-type">ID Document Type</label>
                <select id="reg-id-type" name="id_proof_type" class="lc-reg-select" data-validate="id_proof_type">
                    <option value="">Select type</option>
                    <option value="Aadhaar" @selected(old('id_proof_type') === 'Aadhaar')>Aadhaar</option>
                    <option value="PAN" @selected(old('id_proof_type') === 'PAN')>PAN</option>
                    <option value="Driving License" @selected(old('id_proof_type') === 'Driving License')>Driving License</option>
                    <option value="Voter ID" @selected(old('id_proof_type') === 'Voter ID')>Voter ID</option>
                    <option value="Passport" @selected(old('id_proof_type') === 'Passport')>Passport</option>
                    <option value="Other" @selected(old('id_proof_type') === 'Other')>Other</option>
                </select>
                <p class="lc-reg-error hidden" data-client-error="id_proof_type"></p>
                @error('id_proof_type') <p class="lc-reg-error">{{ $message }}</p> @enderror
            </div>
            <div class="lc-reg-field">
                <label class="lc-reg-label" for="reg-id-file">ID Document File</label>
                <input id="reg-id-file" type="file" name="id_proof" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" class="lc-reg-file" data-validate="id_proof">
                <p class="lc-reg-error hidden" data-client-error="id_proof"></p>
                @error('id_proof') <p class="lc-reg-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="lc-reg-field">
            <label class="lc-reg-label" for="reg-photo">Student Photo (optional)</label>
            <input id="reg-photo" type="file" name="photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="lc-reg-file" data-validate="photo">
            <p class="lc-reg-error hidden" data-client-error="photo"></p>
            @error('photo') <p class="lc-reg-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end border-t border-gray-200 pt-5">
            <button
                type="submit"
                id="student-reg-submit"
                class="inline-flex min-w-[11rem] items-center justify-center gap-2 rounded-xl bg-brand-blue px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-navy focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span class="submit-label">Submit Registration</span>
                <span class="submit-loading hidden items-center gap-2">
                    <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submitting…
                </span>
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('student-registration-form');
            const button = document.getElementById('student-reg-submit');
            const phoneInput = document.getElementById('reg-phone');
            const maxFileBytes = 4 * 1024 * 1024;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phonePattern = /^[6-9]\d{9}$/;

            if (!form || !button) {
                return;
            }

            const fieldConfig = {
                name: { input: () => form.elements.name },
                gender: { input: () => form.elements.gender },
                date_of_birth: { input: () => form.elements.date_of_birth },
                phone: { input: () => form.elements.phone },
                email: { input: () => form.elements.email },
                father_name: { input: () => form.elements.father_name },
                address: { input: () => form.elements.address },
                id_proof_type: { input: () => form.elements.id_proof_type },
                id_proof: { input: () => form.elements.id_proof },
                photo: { input: () => form.elements.photo },
            };

            const clearClientErrors = () => {
                form.querySelectorAll('[data-client-error]').forEach((node) => {
                    node.textContent = '';
                    node.classList.add('hidden');
                });
                form.querySelectorAll('.lc-reg-input--error, .lc-reg-select--error, .lc-reg-file--error').forEach((el) => {
                    el.classList.remove('lc-reg-input--error', 'lc-reg-select--error', 'lc-reg-file--error');
                });
            };

            const setFieldError = (field, message) => {
                const config = fieldConfig[field];
                const input = config?.input();
                const errorNode = form.querySelector(`[data-client-error="${field}"]`);

                if (input) {
                    if (input.classList.contains('lc-reg-select')) {
                        input.classList.add('lc-reg-select--error');
                    } else if (input.classList.contains('lc-reg-file')) {
                        input.classList.add('lc-reg-file--error');
                    } else {
                        input.classList.add('lc-reg-input--error');
                    }
                }

                if (errorNode) {
                    errorNode.textContent = message;
                    errorNode.classList.remove('hidden');
                }
            };

            const validateFile = (file, allowedExtensions) => {
                if (!file) {
                    return null;
                }

                const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

                if (!allowedExtensions.includes(extension)) {
                    return `File must be ${allowedExtensions.join(', ').toUpperCase()}.`;
                }

                if (file.size > maxFileBytes) {
                    return 'File must be 4 MB or smaller.';
                }

                return null;
            };

            const validateForm = () => {
                const errors = {};
                const name = String(form.elements.name?.value || '').trim();
                const gender = String(form.elements.gender?.value || '');
                const dob = String(form.elements.date_of_birth?.value || '');
                const phone = String(form.elements.phone?.value || '').trim();
                const email = String(form.elements.email?.value || '').trim();
                const fatherName = String(form.elements.father_name?.value || '').trim();
                const address = String(form.elements.address?.value || '').trim();
                const idType = String(form.elements.id_proof_type?.value || '').trim();
                const idFile = form.elements.id_proof?.files?.[0] || null;
                const photoFile = form.elements.photo?.files?.[0] || null;

                if (name.length < 2) {
                    errors.name = 'Full name must be at least 2 characters.';
                }

                if (!['male', 'female'].includes(gender)) {
                    errors.gender = 'Select gender.';
                }

                if (!dob) {
                    errors.date_of_birth = 'Select date of birth.';
                } else {
                    const selected = new Date(`${dob}T00:00:00`);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (Number.isNaN(selected.getTime()) || selected >= today) {
                        errors.date_of_birth = 'Date of birth must be before today.';
                    }
                }

                if (!phonePattern.test(phone)) {
                    errors.phone = 'Enter a valid 10-digit Indian mobile number (starts with 6–9).';
                }

                if (!emailPattern.test(email)) {
                    errors.email = 'Enter a valid email address.';
                }

                if (fatherName.length > 255) {
                    errors.father_name = 'Father’s name is too long.';
                }

                if (address.length > 1000) {
                    errors.address = 'Address is too long (max 1000 characters).';
                }

                if (idFile && !idType) {
                    errors.id_proof_type = 'Select an ID document type when uploading a file.';
                }

                if (idType && !idFile) {
                    errors.id_proof = 'Upload an ID document file for the selected type.';
                }

                const idProofError = validateFile(idFile, ['jpg', 'jpeg', 'png', 'pdf']);
                if (idProofError) {
                    errors.id_proof = idProofError;
                }

                const photoError = validateFile(photoFile, ['jpg', 'jpeg', 'png']);
                if (photoError) {
                    errors.photo = photoError;
                }

                return errors;
            };

            const resetSubmitButton = () => {
                button.disabled = false;
                button.querySelector('.submit-label')?.classList.remove('hidden');
                const loading = button.querySelector('.submit-loading');
                loading?.classList.add('hidden');
                loading?.classList.remove('inline-flex');
            };

            const setSubmitting = () => {
                button.disabled = true;
                button.querySelector('.submit-label')?.classList.add('hidden');
                const loading = button.querySelector('.submit-loading');
                loading?.classList.remove('hidden');
                loading?.classList.add('inline-flex');
            };

            phoneInput?.addEventListener('input', () => {
                phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 10);
            });

            form.addEventListener('submit', (event) => {
                clearClientErrors();
                const errors = validateForm();

                if (Object.keys(errors).length > 0) {
                    event.preventDefault();
                    resetSubmitButton();
                    Object.entries(errors).forEach(([field, message]) => setFieldError(field, message));
                    const firstInvalid = form.querySelector('.lc-reg-input--error, .lc-reg-select--error, .lc-reg-file--error');
                    firstInvalid?.focus();
                    return;
                }

                setSubmitting();
            });
        });
    </script>
</x-students.registration-shell>
