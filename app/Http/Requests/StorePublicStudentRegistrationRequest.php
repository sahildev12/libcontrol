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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ValidationRules::phoneRequired(),
            'email' => ValidationRules::emailRequired(),
            'father_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'id_proof_type' => ['nullable', 'string', 'max:100', 'required_with:id_proof'],
            'id_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096', 'required_with:id_proof_type'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the student’s full name.',
            'name.min' => 'Full name must be at least 2 characters.',
            'date_of_birth.required' => 'Select date of birth.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'phone.required' => 'Enter a 10-digit mobile number.',
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number (starts with 6–9).',
            'email.required' => 'Enter an email address.',
            'email.email' => 'Enter a valid email address.',
            'id_proof_type.required_with' => 'Select an ID document type when uploading a file.',
            'id_proof.required_with' => 'Upload an ID document file for the selected type.',
            'id_proof.mimes' => 'ID document must be JPG, PNG, or PDF.',
            'id_proof.max' => 'ID document must be 4 MB or smaller.',
            'photo.mimes' => 'Photo must be JPG or PNG.',
            'photo.max' => 'Photo must be 4 MB or smaller.',
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
