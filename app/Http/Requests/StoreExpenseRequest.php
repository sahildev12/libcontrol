<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category' => ['required', 'string', Rule::in(array_keys(config('libcontrol.expense_categories', [])))],
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'upi', 'card', 'bank_transfer', 'other'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
