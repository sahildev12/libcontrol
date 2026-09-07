<?php

namespace App\Services;

use App\Models\FamilyGroup;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentFamilyService
{
    /**
     * @param  array<string, mixed>  $contact
     */
    public function createGroup(int $branchId, array $contact): FamilyGroup
    {
        return FamilyGroup::query()->create([
            'branch_id' => $branchId,
            'guardian_name' => $contact['guardian_name'] ?? $contact['father_name'] ?? null,
            'phone' => $this->normalizePhone($contact['phone'] ?? null),
            'email' => $this->normalizeEmail($contact['email'] ?? null),
            'address' => $contact['address'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $contact
     */
    public function updateGroup(FamilyGroup $group, array $contact): FamilyGroup
    {
        $group->update([
            'guardian_name' => $contact['guardian_name'] ?? $contact['father_name'] ?? $group->guardian_name,
            'phone' => array_key_exists('phone', $contact)
                ? $this->normalizePhone($contact['phone'])
                : $group->phone,
            'email' => array_key_exists('email', $contact)
                ? $this->normalizeEmail($contact['email'])
                : $group->email,
            'address' => $contact['address'] ?? $group->address,
        ]);

        return $group->fresh();
    }

    public function linkStudent(Student $student, FamilyGroup $group, bool $asPrimary = false): Student
    {
        abort_unless($student->branch_id === $group->branch_id, 422, 'Family group must belong to the same branch.');

        if ($asPrimary) {
            $group->students()->update(['is_family_primary' => false]);
        }

        $student->update([
            'family_group_id' => $group->id,
            'is_family_primary' => $asPrimary,
            'phone' => null,
            'email' => null,
            'father_name' => $group->guardian_name ?: $student->father_name,
            'address' => $group->address ?: $student->address,
        ]);

        return $student->fresh(['familyGroup']);
    }

    public function unlinkStudent(Student $student): Student
    {
        return DB::transaction(function () use ($student) {
            $group = $student->familyGroup;
            $wasPrimary = (bool) $student->is_family_primary;

            $student->update([
                'family_group_id' => null,
                'is_family_primary' => false,
                'phone' => $group?->phone,
                'email' => $group?->email,
                'father_name' => $group?->guardian_name ?: $student->father_name,
                'address' => $group?->address ?: $student->address,
            ]);

            if ($group) {
                $remaining = $group->students()->where('id', '!=', $student->id)->orderBy('id')->get();

                if ($remaining->isEmpty()) {
                    $group->delete();
                } elseif ($wasPrimary) {
                    $remaining->first()?->update(['is_family_primary' => true]);
                }
            }

            return $student->fresh();
        });
    }

    public function promotePrimary(Student $student): void
    {
        $group = $student->familyGroup;
        abort_unless($group, 422, 'Student is not linked to a family.');

        $group->students()->update(['is_family_primary' => false]);
        $student->update(['is_family_primary' => true]);
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $value = trim((string) $phone);

        return $value === '' ? null : $value;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $value = trim((string) $email);

        return $value === '' ? null : strtolower($value);
    }
}
