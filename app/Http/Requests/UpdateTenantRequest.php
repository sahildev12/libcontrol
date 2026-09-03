<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
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
        $tenantId = $this->route('tenant')?->id;

        return [
            'client_name' => ['required', 'string', 'max:120'],
            'plan_tier' => ['required', 'string', Rule::in(array_keys(config('libcontrol.plans', [])))],
            'max_seats_override' => ['nullable', 'integer', 'min:1'],
            'max_halls_override' => ['nullable', 'integer', 'min:1'],
            'max_branches_override' => ['nullable', 'integer', 'min:1'],
            'active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
