<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_code_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'student_code_padding' => ['required', 'integer', 'min:1', 'max:6'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'logo_with_text' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:4096'],
            'simple_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:4096'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_code_prefix.required' => 'Student code prefix is required.',
            'student_code_prefix.regex' => 'Use only letters, numbers, hyphens, or underscores.',
            'student_code_padding.required' => 'Number padding is required.',
        ];
    }
}
