<?php

namespace App\Http\Requests;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'login_email' => $this->filled('login_email') ? trim((string) $this->input('login_email')) : null,
            'contact_person' => $this->filled('contact_person') ? trim((string) $this->input('contact_person')) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');
        $loginUserId = $branch?->users()->orderBy('id')->value('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ValidationRules::phoneOptional(),
            'email' => ValidationRules::emailOptional(),
            'login_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($loginUserId)],
            'password' => ['nullable', 'string', 'min:8'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Branch name is required.',
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'email.email' => 'Enter a valid email address.',
            'login_email.unique' => 'This login email is already in use.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }
}
