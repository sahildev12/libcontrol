<div x-show="settingsTab === 'emails'" x-cloak class="mt-4 space-y-6">
    @if (! ($mailDeliveryStatus['configured'] ?? false))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">SMTP is not configured</p>
            <p class="mt-1">{{ $mailDeliveryStatus['message'] ?? 'Configure email delivery before students can receive messages.' }}</p>
            <p class="mt-2 text-xs text-red-800">Set <code class="rounded bg-white px-1">MAIL_MAILER=smtp</code>, <code class="rounded bg-white px-1">MAIL_HOST</code>, <code class="rounded bg-white px-1">MAIL_USERNAME</code>, <code class="rounded bg-white px-1">MAIL_PASSWORD</code>, and <code class="rounded bg-white px-1">MAIL_FROM_ADDRESS</code> in your server <code class="rounded bg-white px-1">.env</code> file.</p>
        </div>
    @endif

    @if (($viewingAll ?? false) && ($isClientAdmin ?? false))
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Plan expiry emails</h2>
            <p class="mt-1 text-xs text-gray-500">A reminder email is sent in the morning, this many days before a plan ends. Applies to all branches.</p>
        </div>
        <div class="space-y-4 p-5">
            <div class="max-w-xs">
                <label class="block text-sm font-medium text-gray-700">Send reminder days before expiry</label>
                <input type="number" min="1" max="90" x-model.number="form.expiry_reminder_days" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
            </div>
            <p class="text-xs text-gray-500">Saved with “Save email settings” below.</p>
        </div>
    </section>
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
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_fee_alerts_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Fee alert emails</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Automatic due, overdue, and payment-received emails for students.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_seat_alerts_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Seat alert emails</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Seat expiry reminders for students and vacant / high-occupancy digests for owners.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_subscription_alerts_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Subscription / trial emails</span>
                    <span class="mt-0.5 block text-xs text-gray-500">LibControl plan trial reminders for library owners.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_growth_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Growth tip emails</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Periodic “Grow Your Library” tips for owners (opt-in).</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                <input type="checkbox" x-model="emailForm.email_marketing_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Marketing emails</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Product updates and seasonal campaigns (opt-in only).</span>
                </span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block rounded-lg border border-gray-200 p-4">
                    <span class="block text-sm font-semibold text-gray-900">Vacant seat alert threshold</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Email owners when vacant seats reach this count.</span>
                    <input type="number" min="1" max="10000" x-model.number="emailForm.seat_vacant_alert_threshold" class="mt-2 w-full rounded-lg border-gray-300 text-sm">
                </label>
                <label class="block rounded-lg border border-gray-200 p-4">
                    <span class="block text-sm font-semibold text-gray-900">High occupancy %</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Email owners when occupancy reaches this percent.</span>
                    <input type="number" min="50" max="100" x-model.number="emailForm.seat_high_occupancy_pct" class="mt-2 w-full rounded-lg border-gray-300 text-sm">
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="emailSaving">
                    <span x-show="! emailSaving">Save email settings</span>
                    <span x-show="emailSaving">Saving...</span>
                </button>
            </div>
        </form>
    </section>
</div>
