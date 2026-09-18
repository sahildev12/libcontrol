<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendStudentOfferRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'action_url' => ['nullable', 'string', 'max:500', 'url'],
            'audience' => ['required', Rule::in(['all', 'selected'])],
            'student_ids' => ['exclude_unless:audience,selected', 'required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_ids.required_if' => 'Select at least one student to email.',
            'student_ids.min' => 'Select at least one student to email.',
            'action_url.url' => 'Enter a valid link URL (including https://).',
        ];
    }
}
