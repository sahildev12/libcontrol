<div x-show="settingsTab === 'emails'" x-cloak class="mt-4 space-y-6">
    @if (! ($mailDeliveryStatus['configured'] ?? false))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">SMTP is not configured</p>
            <p class="mt-1">{{ $mailDeliveryStatus['message'] ?? 'Configure email delivery before students can receive messages.' }}</p>
            <p class="mt-2 text-xs text-red-800">Set <code class="rounded bg-white px-1">MAIL_MAILER=smtp</code>, <code class="rounded bg-white px-1">MAIL_HOST</code>, <code class="rounded bg-white px-1">MAIL_USERNAME</code>, <code class="rounded bg-white px-1">MAIL_PASSWORD</code>, and <code class="rounded bg-white px-1">MAIL_FROM_ADDRESS</code> in your server <code class="rounded bg-white px-1">.env</code> file.</p>
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Student email notifications</h2>
            <p class="mt-1 text-xs text-gray-500">Emails are sent only when the student has an email address. Configure SMTP in your server environment.</p>
        </div>
        <form class="space-y-4 p-5" @submit.prevent="saveEmailNotificationSettings()">
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_welcome_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Welcome email</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Sent when a new student is registered.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_birthday_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Birthday email</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Sent automatically on the student birthday.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_offers_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Promotion email</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Used when you send promotional emails to students from the Promotion page.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_recovery_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Recovery email</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Sent to inactive students to encourage them to return.</span>
                </span>
            </label>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="emailSaving">
                    <span x-show="! emailSaving">Save email settings</span>
                    <span x-show="emailSaving">Saving...</span>
                </button>
            </div>
        </form>
    </section>
</div>
