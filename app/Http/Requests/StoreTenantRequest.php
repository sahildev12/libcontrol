<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
            'client_name' => ['required', 'string', 'max:120'],
            'subdomain' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', Rule::unique('tenants', 'subdomain')],
            'database_name' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('tenants', 'database_name')],
            'plan_tier' => ['required', 'string', Rule::in(array_keys(config('libcontrol.plans', [])))],
            'max_seats_override' => ['nullable', 'integer', 'min:1'],
            'max_halls_override' => ['nullable', 'integer', 'min:1'],
            'max_branches_override' => ['nullable', 'integer', 'min:1'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255'],
            'admin_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('subdomain')) {
            $this->merge([
                'subdomain' => Tenant::normalizeSubdomain((string) $this->input('subdomain')),
            ]);
        }
    }
}
