<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFeeRequest extends FormRequest
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
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fee_type' => ['required', Rule::in(['monthly', 'yearly', 'custom', 'one_time', 'membership', 'installment'])],
            'fee_amount' => ['required', 'numeric', 'min:0.01'],
            'joining_date' => ['required', 'date'],
            'plan_expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:joining_date',
                Rule::requiredIf(fn () => in_array($this->input('fee_type'), ['custom'], true)
                    || ($this->input('payment_plan') === 'installments' && $this->input('installment_frequency') === 'custom')),
            ],
            'membership_mode' => ['nullable', Rule::in(['assigned_seat', 'any_seat'])],
            'payment_plan' => ['nullable', Rule::in(['full', 'installments'])],
            'installment_count' => ['nullable', 'integer', 'min:2', 'max:12'],
            'installment_frequency' => [
                'nullable',
                Rule::in(['monthly', 'quarterly', 'half_yearly', 'yearly', 'custom', 'weekly']),
                'required_if:payment_plan,installments',
            ],
            'first_due_date' => ['nullable', 'date', 'required_if:payment_plan,installments'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('fee_type') === 'monthly'
                && $this->input('payment_plan') === 'installments'
                && $this->input('installment_frequency') === 'monthly') {
                $validator->errors()->add('installment_frequency', 'Monthly frequency is not available for monthly fees.');
            }

            if ($this->input('payment_plan') === 'installments'
                && $this->input('installment_frequency') !== 'custom'
                && (! $this->filled('installment_count') || (int) $this->input('installment_count') < 2)) {
                $validator->errors()->add('installment_count', 'Installments must be at least 2.');
            }

            if ($this->input('payment_plan') === 'installments'
                && $this->input('installment_frequency') === 'custom'
                && $this->filled('first_due_date')
                && $this->filled('plan_expiry_date')
                && $this->input('first_due_date') > $this->input('plan_expiry_date')) {
                $validator->errors()->add('first_due_date', 'First due date cannot be after the plan end date.');
            }
        });
    }
}
