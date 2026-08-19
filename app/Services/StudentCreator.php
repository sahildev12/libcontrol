<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Student;
use Illuminate\Http\UploadedFile;

class StudentCreator
{
    public function __construct(
        private StudentCodeService $studentCodeService,
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

        return Student::query()->create($payload);
    }
}
