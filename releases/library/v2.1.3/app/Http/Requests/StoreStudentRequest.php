<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Services\StudentContactValidator;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'student_type' => ['nullable', Rule::in(['regular', 'trial'])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'family_group_id' => ['nullable', 'integer', 'exists:family_groups,id'],
            'link_to_student_id' => ['nullable', 'integer', 'exists:students,id'],
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
        if ($this->filled('branch_id')) {
            return Branch::query()->find($this->integer('branch_id'));
        }

        $userBranchId = $this->user()?->branch_id;

        return $userBranchId ? Branch::query()->find($userBranchId) : null;
    }
}
