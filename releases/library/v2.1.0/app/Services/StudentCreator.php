<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\FamilyGroup;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StudentCreator
{
    public function __construct(
        private StudentCodeService $studentCodeService,
        private StudentFamilyService $studentFamilyService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Branch $branch, array $data, ?UploadedFile $photo = null, ?UploadedFile $idProof = null): Student
    {
        abort_unless(
            $this->studentCodeService->prefixIsConfigured(),
            422,
            'Set the global student code prefix in Settings before creating students.',
        );

        return DB::transaction(function () use ($branch, $data, $photo, $idProof) {
            $familyGroupId = $data['family_group_id'] ?? null;
            $linkToStudentId = $data['link_to_student_id'] ?? null;
            unset($data['family_group_id'], $data['link_to_student_id']);

            $payload = collect($data)->only([
                'name',
                'gender',
                'date_of_birth',
                'phone',
                'email',
                'father_name',
                'address',
                'id_proof_type',
                'status',
                'student_type',
            ])->all();

            $payload['phone'] = filled($payload['phone'] ?? null) ? trim((string) $payload['phone']) : null;
            $payload['email'] = filled($payload['email'] ?? null) ? strtolower(trim((string) $payload['email'])) : null;
            $payload['branch_id'] = $branch->id;
            $payload['student_code'] = $this->studentCodeService->generate($branch);
            $payload['status'] = $payload['status'] ?? 'active';
            $payload['student_type'] = $payload['student_type'] ?? Student::TYPE_REGULAR;

            if ($photo) {
                $payload['photo_path'] = $photo->store('student-photos/'.$branch->id, 'public');
            }

            if ($idProof) {
                $payload['id_proof_path'] = $idProof->store('id-proofs/'.$branch->id, 'local');
            }

            if ($familyGroupId || $linkToStudentId) {
                $payload['phone'] = null;
                $payload['email'] = null;
            }

            $student = Student::query()->create($payload);

            if ($familyGroupId) {
                $group = FamilyGroup::query()->findOrFail($familyGroupId);
                $this->studentFamilyService->linkStudent($student, $group);
            } elseif ($linkToStudentId) {
                $anchor = Student::query()->findOrFail($linkToStudentId);
                abort_unless($anchor->branch_id === $branch->id, 422, 'Selected student must belong to the same branch.');

                $group = $anchor->familyGroup;

                if (! $group) {
                    $group = $this->studentFamilyService->createGroup($branch->id, [
                        'guardian_name' => $anchor->father_name,
                        'phone' => $anchor->phone,
                        'email' => $anchor->email,
                        'address' => $anchor->address,
                    ]);

                    $this->studentFamilyService->linkStudent($anchor, $group, true);
                }

                $this->studentFamilyService->linkStudent($student, $group);
            }

            return $student->fresh(['familyGroup', 'branch']);
        });
    }
}
