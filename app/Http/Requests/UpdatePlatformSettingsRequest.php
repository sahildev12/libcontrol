<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $logoLimits = config('libcontrol.id_card_logo_upload', []);
        $maxKb = (int) ($logoLimits['max_kb'] ?? 2048);
        $maxWidth = (int) ($logoLimits['max_width'] ?? 1200);
        $maxHeight = (int) ($logoLimits['max_height'] ?? 600);
        $minWidth = (int) ($logoLimits['min_width'] ?? 80);
        $minHeight = (int) ($logoLimits['min_height'] ?? 32);

        return [
            'student_code_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'student_code_padding' => ['required', 'integer', 'min:1', 'max:6'],
            'id_card_template' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('libcontrol.id_card_templates', [])))],
            'id_card_logo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:'.$maxKb,
                Rule::dimensions()
                    ->maxWidth($maxWidth)
                    ->maxHeight($maxHeight)
                    ->minWidth($minWidth)
                    ->minHeight($minHeight),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $logoLimits = config('libcontrol.id_card_logo_upload', []);
        $maxKb = (int) ($logoLimits['max_kb'] ?? 2048);
        $maxWidth = (int) ($logoLimits['max_width'] ?? 1200);
        $maxHeight = (int) ($logoLimits['max_height'] ?? 600);
        $minWidth = (int) ($logoLimits['min_width'] ?? 80);
        $minHeight = (int) ($logoLimits['min_height'] ?? 32);

        return [
            'student_code_prefix.required' => 'Student code prefix is required.',
            'student_code_prefix.regex' => 'Use only letters, numbers, hyphens, or underscores.',
            'student_code_padding.required' => 'Number padding is required.',
            'id_card_logo.dimensions' => 'Use a logo between '.$minWidth.'×'.$minHeight.' and '.$maxWidth.'×'.$maxHeight.' pixels.',
            'id_card_logo.max' => 'Logo file must be '.$maxKb.' KB or smaller.',
        ];
    }
}
