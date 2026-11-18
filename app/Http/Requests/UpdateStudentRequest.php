<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Services\StudentContactValidator;
use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
            'date_of_birth' => ValidationRules::dateOfBirthRequired(),
            'phone' => ValidationRules::phoneOptional(),
            'email' => ValidationRules::emailOptional(),
            'father_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'id_proof_type' => ['nullable', 'string', 'max:100'],
            'id_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'student_type' => ['required', Rule::in(['regular', 'trial'])],
            'reset_app_login' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'Student must be at least 5 years old to join the library.',
            'photo.max' => 'Photo must be 4 MB or smaller.',
            'id_proof.max' => 'ID document must be 4 MB or smaller.',
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Student|null $student */
        $student = $this->route('student');

        if (! $student?->branch) {
            return;
        }

        app(StudentContactValidator::class)->applyRules(
            $validator,
            $student->branch,
            $this->all(),
            $student,
        );
    }
}
