<?php

namespace App\Http\Requests;

class RenewFeeRequest extends UpdateFeeRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'payment_amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,upi,card,bank_transfer,other'],
            'payment_date' => ['nullable', 'date'],
            'payment_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
