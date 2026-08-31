<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLicensedDeploymentRequest extends FormRequest
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
            'client_name' => ['required', 'string', 'max:120'],
            'allowed_domains' => ['required', 'string', 'max:2000'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:90'],
            'active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
