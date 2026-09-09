<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\FamilyGroup;
use App\Models\Student;
use Illuminate\Validation\Validator;

class StudentContactValidator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function applyRules(Validator $validator, Branch $branch, array $data, ?Student $student = null): void
    {
        $validator->after(function (Validator $validator) use ($branch, $data, $student) {
            if (! $branch->require_student_contact) {
                return;
            }

            $familyGroupId = $data['family_group_id'] ?? $student?->family_group_id;
            $familyGroup = $familyGroupId
                ? FamilyGroup::query()->find($familyGroupId)
                : null;

            if ($familyGroup?->hasContact()) {
                return;
            }

            $linkToStudentId = $data['link_to_student_id'] ?? null;
            if ($linkToStudentId) {
                $anchor = Student::query()->with('familyGroup')->find($linkToStudentId);
                if ($anchor && (filled($anchor->effectivePhone()) || filled($anchor->effectiveEmail()))) {
                    return;
                }
            }

            if ($student?->family_group_id && $student->familyGroup?->hasContact()) {
                return;
            }

            $phone = trim((string) ($data['phone'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));

            if ($phone === '' && $email === '') {
                $validator->errors()->add('phone', 'Provide at least a phone number or email address.');
            }
        });
    }

    public function branchRequiresContact(Branch $branch): bool
    {
        return (bool) $branch->require_student_contact;
    }
}
