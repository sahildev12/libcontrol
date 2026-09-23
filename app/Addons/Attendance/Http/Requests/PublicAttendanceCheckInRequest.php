<?php

namespace App\Addons\Attendance\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicAttendanceCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_code_suffix' => ['required', 'string', 'regex:/^\d{1,6}$/'],
            'phone' => ValidationRules::phoneRequired(),
            'action' => ['required', 'string', Rule::in(['check_in', 'check_out'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_code_suffix.required' => 'Enter your student number.',
            'student_code_suffix.regex' => 'Student number must contain digits only (1–6 digits).',
            'phone.required' => 'Enter your 10-digit mobile number.',
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number (starts with 6–9).',
            'action.required' => 'Select check in or check out.',
            'action.in' => 'Select a valid attendance action.',
        ];
    }
}
