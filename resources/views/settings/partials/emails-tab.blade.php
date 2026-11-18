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
                <input type="checkbox" x-model="emailForm.email_finance_digest_enabled" class="mt-1 rounded border-gray-300 text-indigo-600">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Weekly finance digest</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Monday 8:00 AM IST summary for admins only — overdue, collections, and expenses (rate-limited).</span>
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

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Email templates</h2>
            <p class="mt-1 text-xs text-gray-500">Edit subject and body wording. Use placeholders like <code class="rounded bg-gray-100 px-1">{name}</code>, <code class="rounded bg-gray-100 px-1">{library}</code>, and <code class="rounded bg-gray-100 px-1">{student_code}</code>.</p>
        </div>
        <div class="divide-y divide-gray-100">
            <template x-for="tpl in emailTemplates" :key="tpl.key">
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-gray-900" x-text="tpl.label"></p>
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                :class="tpl.customized ? 'bg-amber-50 text-amber-800' : 'bg-gray-100 text-gray-600'"
                                x-text="tpl.customized ? 'Custom' : 'Default'"
                            ></span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500" x-text="tpl.description"></p>
                        <p class="mt-2 truncate text-xs text-gray-600"><span class="font-medium text-gray-700">Subject:</span> <span x-text="tpl.subject"></span></p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" @click="openEmailTemplateEditor(tpl)">Edit</button>
                    </div>
                </div>
            </template>
        </div>
    </section>

    <div
        x-show="emailTemplateEditor.open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4"
        @keydown.escape.window="closeEmailTemplateEditor()"
    >
        <div class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl" @click.outside="closeEmailTemplateEditor()">
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="text-base font-semibold text-gray-900" x-text="emailTemplateEditor.label || 'Edit template'"></h3>
                <p class="mt-1 text-xs text-gray-500">Placeholders: <span x-text="(emailTemplateEditor.placeholders || []).map(p => '{' + p + '}').join(', ')"></span></p>
            </div>
            <div class="space-y-4 px-5 py-4">
                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Subject</span>
                    <input type="text" x-model="emailTemplateEditor.subject" maxlength="200" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                </label>
                <label class="block" x-show="emailTemplateEditor.supportsHeadline">
                    <span class="text-sm font-medium text-gray-700">Headline</span>
                    <input type="text" x-model="emailTemplateEditor.headline" maxlength="200" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-gray-700">Body</span>
                    <textarea x-model="emailTemplateEditor.body" rows="10" maxlength="10000" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm shadow-sm"></textarea>
                </label>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                <div class="flex gap-2">
                    <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-white" @click="resetEmailTemplate()" :disabled="emailTemplateEditor.saving">Reset to default</button>
                    <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-white" @click="sendTestEmailTemplate()" :disabled="emailTemplateEditor.saving">Send test</button>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-white" @click="closeEmailTemplateEditor()" :disabled="emailTemplateEditor.saving">Cancel</button>
                    <button type="button" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700" @click="saveEmailTemplate()" :disabled="emailTemplateEditor.saving">
                        <span x-show="! emailTemplateEditor.saving">Save template</span>
                        <span x-show="emailTemplateEditor.saving">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
