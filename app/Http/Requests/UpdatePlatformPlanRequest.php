<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDeveloperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_tier' => ['required', 'string', Rule::in(array_keys(config('libcontrol.plans', [])))],
            'max_seats_override' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'max_halls_override' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'max_branches_override' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
