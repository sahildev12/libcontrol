<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->branch_id !== null || $user->isPlatformAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_open_24_hours')) {
            $this->merge([
                'is_open_24_hours' => filter_var($this->input('is_open_24_hours'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('is_open_24_hours')) {
                return;
            }

            $open = $this->input('library_open_time');
            $close = $this->input('library_close_time');

            if ($open && $close && $close <= $open) {
                $validator->errors()->add('library_close_time', 'Closing time must be after opening time.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'expiry_reminder_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'library_open_time' => ['nullable', 'date_format:H:i'],
            'library_close_time' => ['nullable', 'date_format:H:i'],
            'is_open_24_hours' => ['nullable', 'boolean'],
        ];
    }
}
