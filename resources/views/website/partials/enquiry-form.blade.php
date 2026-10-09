@php
    $formId = $formId ?? 'lw-enquiry-form';
    $submitId = $submitId ?? 'lw-enquiry-submit';
    $okId = $okId ?? 'lw-enquiry-ok';
    $errId = $errId ?? 'lw-enquiry-err';
    $referralOffer = $site['referral_offer'] ?? null;
@endphp

<form id="{{ $formId }}" class="space-y-4" novalidate>
    @csrf
    <div>
        <p class="text-lg font-bold text-slate-900">Request a call back</p>
        <p class="mt-1 text-sm text-slate-500">We usually reply within a few hours.</p>
    </div>
    <div class="hidden" aria-hidden="true">
        <label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div data-lw-field="name">
            <label for="{{ $formId }}-name" class="mb-1.5 block text-xs font-semibold text-slate-600">Your name <span class="text-red-500">*</span></label>
            <input id="{{ $formId }}-name" type="text" name="name" required minlength="2" maxlength="80" autocomplete="name" placeholder="Full name" class="lw-input">
            <p class="lw-field-error" data-lw-error></p>
        </div>
        <div data-lw-field="phone">
            <label for="{{ $formId }}-phone" class="mb-1.5 block text-xs font-semibold text-slate-600">Phone <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-medium text-slate-500">+91</span>
                <input id="{{ $formId }}-phone" type="tel" name="phone" required inputmode="numeric" maxlength="10" autocomplete="tel-national" placeholder="10-digit mobile" class="lw-input !pl-12">
            </div>
            <p class="lw-field-error" data-lw-error></p>
        </div>
    </div>
    <div data-lw-field="email">
        <label for="{{ $formId }}-email" class="mb-1.5 block text-xs font-semibold text-slate-600">Email <span class="font-normal text-slate-400">(optional)</span></label>
        <input id="{{ $formId }}-email" type="email" name="email" maxlength="255" autocomplete="email" placeholder="you@example.com" class="lw-input">
        <p class="lw-field-error" data-lw-error></p>
    </div>
    <div data-lw-field="message">
        <div class="mb-1.5 flex items-center justify-between">
            <label for="{{ $formId }}-message" class="block text-xs font-semibold text-slate-600">Message <span class="font-normal text-slate-400">(optional)</span></label>
            <span class="text-[11px] text-slate-400" data-lw-counter>0 / 1000</span>
        </div>
        <textarea id="{{ $formId }}-message" name="message" rows="4" maxlength="1000" placeholder="Plan interested, preferred seat, trial date…" class="lw-input"></textarea>
        <p class="lw-field-error" data-lw-error></p>
    </div>
    @if ($referralOffer)
        <div data-lw-field="referred_by">
            <label for="{{ $formId }}-referred-by" class="mb-1.5 block text-xs font-semibold text-slate-600">Referred by a student? <span class="font-normal text-slate-400">(optional)</span></label>
            <input id="{{ $formId }}-referred-by" type="text" name="referred_by" maxlength="40" autocomplete="off" placeholder="Enter their Student ID, e.g. SUN-001" class="lw-input uppercase placeholder:normal-case">
            <p class="mt-1 text-[11px] font-medium text-emerald-700">{{ $referralOffer }}</p>
            <p class="lw-field-error" data-lw-error></p>
        </div>
    @endif
    <button type="submit" id="{{ $submitId }}" class="lw-btn-primary w-full !py-3.5 disabled:cursor-not-allowed disabled:opacity-70">Send enquiry</button>
    <p id="{{ $okId }}" class="hidden rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status">Thank you! We received your enquiry and will call you back soon.</p>
    <p id="{{ $errId }}" class="hidden rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-600" role="alert"></p>
</form>

<script>
    (function () {
        const form = document.getElementById(@json($formId));
        if (!form || form.dataset.lwEnquiryBound === '1') return;
        form.dataset.lwEnquiryBound = '1';
        const submitBtn = document.getElementById(@json($submitId));
        const ok = document.getElementById(@json($okId));
        const err = document.getElementById(@json($errId));
        const counter = form.querySelector('[data-lw-counter]');

        const NAME_RE = /^[\p{L}][\p{L}\s.'-]*$/u;
        const PHONE_RE = /^[6-9]\d{9}$/;
        const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

        const validators = {
            name(value) {
                if (!value) return 'Please enter your name.';
                if (value.length < 2) return 'Name must be at least 2 characters.';
                if (value.length > 80) return 'Name must be 80 characters or fewer.';
                if (!NAME_RE.test(value)) return 'Name can only contain letters, spaces, dots, hyphens and apostrophes.';
                return '';
            },
            phone(value) {
                if (!value) return 'Please enter your phone number.';
                if (!/^\d+$/.test(value)) return 'Phone number can only contain digits.';
                if (value.length !== 10) return 'Enter a 10-digit mobile number.';
                if (!PHONE_RE.test(value)) return 'Enter a valid Indian mobile number (starts with 6, 7, 8 or 9).';
                return '';
            },
            email(value) {
                if (!value) return '';
                if (value.length > 255 || !EMAIL_RE.test(value)) return 'Enter a valid email address, e.g. you@example.com.';
                return '';
            },
            message(value) {
                if (value.length > 1000) return 'Message must be 1000 characters or fewer.';
                return '';
            },
            referred_by(value) {
                if (!value) return '';
                if (!/^[A-Z0-9][A-Z0-9-]{1,39}$/i.test(value)) return 'Enter a valid Student ID, e.g. SUN-001.';
                return '';
            },
        };

        const fieldEl = (name) => form.querySelector(`[data-lw-field="${name}"]`);
        const inputEl = (name) => form.elements.namedItem(name);
        const valueOf = (name) => String(inputEl(name)?.value ?? '').trim();

        const setError = (name, message) => {
            const wrap = fieldEl(name);
            const input = inputEl(name);
            if (!wrap || !input) return;
            wrap.querySelector('[data-lw-error]').textContent = message;
            wrap.classList.toggle('has-error', message !== '');
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
        };

        const validateField = (name) => {
            const message = validators[name](valueOf(name));
            setError(name, message);
            return message === '';
        };

        const validateAll = () => {
            let firstInvalid = null;
            Object.keys(validators).forEach((name) => {
                if (!validateField(name) && !firstInvalid) firstInvalid = name;
            });
            if (firstInvalid) inputEl(firstInvalid)?.focus();
            return firstInvalid === null;
        };

        inputEl('phone')?.addEventListener('input', (e) => {
            const digits = e.target.value.replace(/\D+/g, '').replace(/^(?:91|0)(?=\d{10}$)/, '').slice(0, 10);
            if (digits !== e.target.value) e.target.value = digits;
        });

        inputEl('referred_by')?.addEventListener('input', (e) => {
            const cleaned = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
            if (cleaned !== e.target.value) e.target.value = cleaned;
        });

        inputEl('message')?.addEventListener('input', (e) => {
            if (counter) counter.textContent = `${e.target.value.length} / 1000`;
        });

        Object.keys(validators).forEach((name) => {
            const input = inputEl(name);
            input?.addEventListener('blur', () => {
                if (valueOf(name) !== '' || name === 'name' || name === 'phone') validateField(name);
            });
            input?.addEventListener('input', () => {
                if (fieldEl(name)?.classList.contains('has-error')) validateField(name);
            });
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            ok.classList.add('hidden');
            err.classList.add('hidden');

            if (!validateAll()) return;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending…';
            try {
                const body = Object.fromEntries(new FormData(form).entries());
                delete body._token;
                Object.keys(body).forEach((key) => { body[key] = String(body[key]).trim(); });
                const res = await fetch(@json(route('website.enquiries.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json().catch(() => ({}));
                if (res.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([name, messages]) => setError(name, messages[0]));
                    const first = Object.keys(data.errors).find((name) => inputEl(name));
                    if (first) inputEl(first).focus();
                    return;
                }
                if (res.status === 429) throw new Error('Too many attempts. Please wait a minute and try again.');
                if (!res.ok) throw new Error(data.message || 'Could not send enquiry. Please try again.');
                form.reset();
                if (counter) counter.textContent = '0 / 1000';
                Object.keys(validators).forEach((name) => setError(name, ''));
                ok.classList.remove('hidden');
            } catch (ex) {
                err.textContent = ex.message || 'Could not send enquiry. Please try again.';
                err.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send enquiry';
            }
        });
    })();
</script>
