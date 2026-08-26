<?php

namespace App\Http\Requests;

use App\Services\PlanExpiryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSeatBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $type = (string) $this->input('fee_type');

        if ($type === 'installment') {
            $this->merge([
                'fee_type' => 'monthly',
                'payment_plan' => $this->input('payment_plan', 'installments'),
            ]);
        }

        if (in_array($type, ['one-time', 'onetime'], true)) {
            $this->merge(['fee_type' => 'one_time']);
        }

        if ($this->input('fee_type') === 'one_time' || $type === 'one_time') {
            $this->merge(['payment_plan' => 'full']);
        }

        // Flexible installments: pay as received, no schedule.
        if ($this->input('payment_plan') === 'installments' && ! $this->filled('installment_frequency')) {
            $this->merge(['installment_frequency' => 'custom']);
        }

        $amountReceived = $this->input('amount_received');
        if ($amountReceived === '' || $amountReceived === null) {
            $this->merge(['amount_received' => 0]);
        }

        // Auto-fill expiry when omitted (Fees page still relies on server calculation).
        if (! $this->filled('plan_expiry_date') && $this->filled('joining_date')) {
            $feeType = (string) $this->input('fee_type', 'monthly');
            if ($feeType !== 'custom') {
                $joining = Carbon::parse($this->input('joining_date'));
                $expiry = app(PlanExpiryService::class)->calculate($feeType, $joining);
                $this->merge(['plan_expiry_date' => $expiry->toDateString()]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $feeAmount = (float) $this->input('fee_amount', 0);

        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'seat_id' => ['required', 'integer', 'exists:seats,id'],
            'time_slot' => ['required', Rule::in(['full_day', 'custom_hours'])],
            'custom_start_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i'],
            'custom_end_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i', 'after:custom_start_time'],
            'fee_type' => ['required', Rule::in(['monthly', 'yearly', 'custom', 'one_time', 'membership', 'installment'])],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'joining_date' => ['required', 'date'],
            'plan_expiry_date' => [
                'required',
                'date',
                'after_or_equal:joining_date',
            ],
            'membership_mode' => ['nullable', Rule::in(['assigned_seat', 'any_seat'])],
            'payment_plan' => ['nullable', Rule::in(['full', 'installments'])],
            'installment_count' => ['nullable', 'integer', 'min:2', 'max:12'],
            'installment_frequency' => [
                'nullable',
                Rule::in(['monthly', 'quarterly', 'half_yearly', 'yearly', 'custom', 'weekly']),
            ],
            'first_due_date' => ['nullable', 'date'],
            'amount_received' => ['nullable', 'numeric', 'min:0', 'max:'.$feeAmount],
            'payment_method' => [
                'nullable',
                Rule::requiredIf(fn () => (float) $this->input('amount_received', 0) > 0),
                Rule::in(['cash', 'upi', 'card', 'bank_transfer', 'other']),
            ],
            'payment_date' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => (float) $this->input('amount_received', 0) > 0),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $amountReceived = round((float) $this->input('amount_received', 0), 2);
            $feeAmount = round((float) $this->input('fee_amount', 0), 2);

            if ($amountReceived < 0) {
                $validator->errors()->add('amount_received', 'Payment amount must be greater than or equal to 0.');
            }

            if ($amountReceived > $feeAmount + 0.009) {
                $validator->errors()->add('amount_received', 'Payment amount cannot exceed the remaining fee.');
            }

            if ($amountReceived > 0 && $amountReceived < 0.01) {
                $validator->errors()->add('amount_received', 'Payment amount must be greater than 0.');
            }
        });
    }
}
