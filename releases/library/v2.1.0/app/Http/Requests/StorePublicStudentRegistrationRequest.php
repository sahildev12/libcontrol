<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\StudentRegistrationInvite;
use App\Services\StudentContactValidator;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicStudentRegistrationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ValidationRules::phoneOptional(),
            'email' => ValidationRules::emailOptional(),
            'father_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'id_proof_type' => ['nullable', 'string', 'max:100'],
            'id_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ];
    }

    public function withValidator($validator): void
    {
        $branch = $this->resolveBranch();

        if (! $branch) {
            return;
        }

        app(StudentContactValidator::class)->applyRules(
            $validator,
            $branch,
            $this->all(),
        );
    }

    private function resolveBranch(): ?Branch
    {
        $token = $this->route('token');

        if (! $token) {
            return null;
        }

        $invite = StudentRegistrationInvite::query()
            ->where('token', $token)
            ->with('branch')
            ->first();

        return $invite?->branch;
    }
}
