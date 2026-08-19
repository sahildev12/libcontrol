<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeatBookingRequest extends FormRequest
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
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'seat_id' => ['required', 'integer', 'exists:seats,id'],
            'time_slot' => ['required', Rule::in(['full_day', 'custom_hours'])],
            'custom_start_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i'],
            'custom_end_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i', 'after:custom_start_time'],
            'fee_type' => ['required', Rule::in(['monthly', 'yearly', 'custom', 'membership'])],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'joining_date' => ['required', 'date'],
            'plan_expiry_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'membership_mode' => ['nullable', Rule::in(['assigned_seat', 'any_seat'])],
        ];
    }
}
