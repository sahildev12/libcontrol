<div x-show="renewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/50" @click="closeRenew()"></div>
    <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white shadow-xl" @click.stop>
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="text-lg font-semibold text-gray-900">Renew plan</h3>
        </div>
        <form @submit.prevent="submitRenew()" class="space-y-4 p-5">
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                <p class="font-medium text-gray-900" x-text="renewRow?.student_name"></p>
                <p class="text-xs text-gray-500" x-text="renewRow?.student_code"></p>
                <p class="mt-1 text-xs text-gray-600" x-text="renewSummary()"></p>
                <p class="mt-2 text-xs text-amber-800" x-show="renewRow?.plan_expiry_date">
                    Current plan ends <span class="font-semibold" x-text="renewRow?.plan_expiry_date"></span>
                </p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fee Type</label>
                    <select x-model="renewForm.fee_type" @change="onRenewOptionsChanged()" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="custom">Custom</option>
                        <option value="membership">Membership</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fee Amount</label>
                    <input type="number" min="0.01" step="0.01" x-model.number="renewForm.fee_amount" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">New period starts</label>
                    <input type="date" x-model="renewForm.joining_date" @change="onRenewOptionsChanged()" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                </div>
                <div x-show="renewShowsEndDate()">
                    <label class="block text-sm font-medium text-gray-700">Plan end date</label>
                    <input
                        type="date"
                        x-model="renewForm.plan_expiry_date"
                        @change="onRenewOptionsChanged()"
                        :readonly="! renewEndDateEditable()"
                        :required="renewEndDateRequired()"
                        :class="renewEndDateEditable() ? 'mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm' : lockedFieldClass()"
                    >
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Payment plan</label>
                <select x-model="renewForm.payment_plan" @change="onRenewOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    <option value="full">Full payment</option>
                    <option value="installments">Installments</option>
                </select>
            </div>
            <div x-show="renewForm.payment_plan === 'installments'" class="space-y-4 rounded-lg border border-gray-100 p-3">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Installment frequency</label>
                        <select x-model="renewForm.installment_frequency" @change="onRenewOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="weekly">Weekly</option>
                            <option value="monthly" :disabled="renewForm.fee_type === 'monthly'">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="half_yearly">Half-yearly</option>
                            <option value="yearly">Yearly</option>
                            <option value="custom">Flexible (custom dates)</option>
                        </select>
                    </div>
                    <div x-show="renewForm.installment_frequency !== 'custom'">
                        <label class="block text-sm font-medium text-gray-700">Installment count</label>
                        <input type="number" min="2" max="12" x-model.number="renewForm.installment_count" @change="onRenewOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">First due date</label>
                    <input type="date" x-model="renewForm.first_due_date" @change="onRenewOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                </div>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3">
                <p class="text-sm font-semibold text-emerald-900">Payment received now <span class="font-normal text-emerald-700">(optional)</span></p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" min="0" step="0.01" x-model.number="renewForm.payment_amount" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment method</label>
                        <select x-model="renewForm.payment_method" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700">Payment date</label>
                    <input type="date" x-model="renewForm.payment_date" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                <button type="button" @click="closeRenew()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" :disabled="renewSaving" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50" x-text="renewSaving ? 'Renewing...' : 'Renew plan'"></button>
            </div>
        </form>
    </div>
</div>
