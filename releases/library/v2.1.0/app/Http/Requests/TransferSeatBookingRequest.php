<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferSeatBookingRequest extends FormRequest
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
            'booking_id' => ['required', 'integer', 'exists:seat_bookings,id'],
            'hall_id' => ['required', 'integer', 'exists:halls,id'],
            'seat_id' => ['required', 'integer', 'exists:seats,id'],
            'time_slot' => ['required', Rule::in(['full_day', 'custom_hours'])],
            'custom_start_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i'],
            'custom_end_time' => ['nullable', 'required_if:time_slot,custom_hours', 'date_format:H:i', 'after:custom_start_time'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'custom_end_time.after' => 'End time must be after start time.',
            'seat_id.required' => 'Select a new seat.',
            'hall_id.required' => 'Select a hall.',
        ];
    }
}
