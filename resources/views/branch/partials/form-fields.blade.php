<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Branch name <span class="text-red-500">*</span></label>
        <input
            type="text"
            x-model="{{ $mode === 'create' ? 'createForm.name' : 'editForm.name' }}"
            required
            minlength="2"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
        >
        <p class="mt-1 text-xs text-red-600" x-show="{{ $mode === 'create' ? 'createFormErrors.name' : 'editFormErrors.name' }}" x-text="{{ $mode === 'create' ? 'createFormErrors.name' : 'editFormErrors.name' }}"></p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Contact person name</label>
        <input type="text" x-model="{{ $mode === 'create' ? 'createForm.contact_person' : 'editForm.contact_person' }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Phone number</label>
        <input
            type="tel"
            x-model="{{ $mode === 'create' ? 'createForm.phone' : 'editForm.phone' }}"
            @input="{{ $mode === 'create' ? 'createForm.phone' : 'editForm.phone' }} = sanitizeDigits($event.target.value)"
            inputmode="numeric"
            pattern="[6-9][0-9]{9}"
            maxlength="10"
            placeholder="9876543210"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
        >
        <p class="mt-1 text-xs text-red-600" x-show="{{ $mode === 'create' ? 'createFormErrors.phone' : 'editFormErrors.phone' }}" x-text="{{ $mode === 'create' ? 'createFormErrors.phone' : 'editFormErrors.phone' }}"></p>
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
        <input
            type="email"
            x-model="{{ $mode === 'create' ? 'createForm.email' : 'editForm.email' }}"
            required
            placeholder="admin@branch.example.com"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
        >
        <p class="mt-1 text-xs text-red-600" x-show="{{ $mode === 'create' ? 'createFormErrors.email' : 'editFormErrors.email' }}" x-text="{{ $mode === 'create' ? 'createFormErrors.email' : 'editFormErrors.email' }}"></p>
        <p class="mt-1 text-xs text-gray-500">
            You can use your library admin email. For a separate branch staff login, use a different email.
        </p>
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">
            {{ $mode === 'create' ? 'Password' : 'New password' }}
            @if ($mode === 'create')
                <span class="text-red-500" x-show="! usesAdminEmail(createForm.email)">*</span>
            @endif
        </label>
        <input
            type="text"
            x-model="{{ $mode === 'create' ? 'createForm.password' : 'editForm.password' }}"
            @if ($mode === 'create')
                :required="! usesAdminEmail(createForm.email)"
            @endif
            minlength="8"
            autocomplete="new-password"
            placeholder="{{ $mode === 'create' ? 'At least 8 characters' : 'Leave blank to keep current' }}"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
        >
        <p class="mt-1 text-xs text-red-600" x-show="{{ $mode === 'create' ? 'createFormErrors.password' : 'editFormErrors.password' }}" x-text="{{ $mode === 'create' ? 'createFormErrors.password' : 'editFormErrors.password' }}"></p>
        <button type="button" @click="{{ $mode === 'create' ? 'createForm.password = generatePassword()' : 'editForm.password = generatePassword()' }}" class="mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Generate password</button>
        @if ($mode === 'create')
            <p class="mt-1 text-xs text-gray-500" x-show="usesAdminEmail(createForm.email)" x-cloak>
                Using your admin email — keep signing in with Admin login. Branch password is optional.
            </p>
        @else
            <p class="mt-1 text-xs text-gray-500">Leave blank to keep the current password.</p>
        @endif
    </div>
</div>
