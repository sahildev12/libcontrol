<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrialSeatBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $feeAmount = $this->input('fee_amount');
        if ($feeAmount === null || $feeAmount === '') {
            $this->merge(['fee_amount' => 0]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'seat_id' => ['required', 'integer', 'exists:seats,id'],
            'time_slot' => ['required', 'in:full_day,custom_hours'],
            'custom_start_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i'],
            'custom_end_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i', 'after:custom_start_time'],
            'trial_start' => ['required', 'date'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:14'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
