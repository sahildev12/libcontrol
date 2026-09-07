<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\FamilyGroup;
use App\Models\Student;
use App\Services\StudentCreator;
use App\Services\StudentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private StudentFamilyService $studentFamilyService,
    ) {}

    public function index(Request $request): View
    {
        $students = $this->constrainByActiveBranch(Student::query()->with(['branch:id,name', 'familyGroup']), $request)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Student $student) => $this->serializeStudent($student));

        $viewingAll = $this->viewingAllBranches($request);
        $branchName = $viewingAll ? 'All branches' : ($this->optionalActiveBranch($request)?->name ?? '');
        $branches = $request->user()?->isPlatformAdmin()
            ? \App\Models\Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $defaultBranchId = $this->optionalActiveBranchId($request);
        $activeBranch = $this->optionalActiveBranch($request);
        $requireStudentContact = (bool) ($activeBranch?->require_student_contact ?? false);

        return view('students.index', compact('students', 'viewingAll', 'branchName', 'branches', 'defaultBranchId', 'requireStudentContact'));
    }

    public function show(Request $request, Student $student): JsonResponse
    {
        $this->authorizeStudent($request, $student);

        return response()->json($this->serializeStudent($student, true));
    }

    public function photo(Request $request, Student $student)
    {
        $this->authorizeStudent($request, $student);

        abort_unless($student->photo_path && Storage::disk('public')->exists($student->photo_path), 404);

        return Storage::disk('public')->response($student->photo_path);
    }

    public function idProof(Request $request, Student $student)
    {
        $this->authorizeStudent($request, $student);

        abort_unless($student->id_proof_path && Storage::disk('local')->exists($student->id_proof_path), 404);

        return Storage::disk('local')->response($student->id_proof_path);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'family_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $query = $this->constrainByActiveBranch(
            Student::query()->with(['familyGroup', 'branch:id,name']),
            $request,
        );

        if (! empty($validated['family_phone'])) {
            $phone = trim($validated['family_phone']);
            $query->where(function ($builder) use ($phone) {
                $builder->where('phone', $phone)
                    ->orWhereHas('familyGroup', fn ($family) => $family->where('phone', $phone));
            });
        } elseif (! empty($validated['q'])) {
            $term = trim($validated['q']);
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('student_code', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('familyGroup', function ($family) use ($term) {
                        $family->where('phone', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('guardian_name', 'like', "%{$term}%");
                    });
            });
        } else {
            return response()->json(['results' => []]);
        }

        $results = $query->orderBy('name')->limit(15)->get()
            ->map(fn (Student $student) => $this->serializeStudent($student, true));

        return response()->json(['results' => $results]);
    }

    public function linkFamily(Request $request, Student $student): JsonResponse
    {
        $this->authorizeStudent($request, $student);

        $validated = $request->validate([
            'family_group_id' => ['nullable', 'integer', 'exists:family_groups,id'],
            'link_to_student_id' => ['nullable', 'integer', 'exists:students,id'],
        ]);

        abort_if(
            empty($validated['family_group_id']) && empty($validated['link_to_student_id']),
            422,
            'Select a family or student to link.',
        );

        if (! empty($validated['family_group_id'])) {
            $group = FamilyGroup::query()->findOrFail($validated['family_group_id']);
            $this->studentFamilyService->linkStudent($student, $group);
        } else {
            $anchor = Student::query()->with('familyGroup')->findOrFail($validated['link_to_student_id']);
            abort_unless($anchor->branch_id === $student->branch_id, 422, 'Students must belong to the same branch.');

            $group = $anchor->familyGroup;

            if (! $group) {
                $group = $this->studentFamilyService->createGroup($student->branch_id, [
                    'guardian_name' => $anchor->father_name,
                    'phone' => $anchor->phone,
                    'email' => $anchor->email,
                    'address' => $anchor->address,
                ]);
                $this->studentFamilyService->linkStudent($anchor, $group, true);
            }

            $this->studentFamilyService->linkStudent($student, $group);
        }

        return response()->json([
            'message' => 'Student linked to family.',
            'student' => $this->serializeStudent($student->fresh(['familyGroup', 'branch'])),
        ]);
    }

    public function unlinkFamily(Request $request, Student $student): JsonResponse
    {
        $this->authorizeStudent($request, $student);

        abort_unless($student->family_group_id, 422, 'Student is not linked to a family.');

        $student = $this->studentFamilyService->unlinkStudent($student);

        return response()->json([
            'message' => 'Student unlinked from family.',
            'student' => $this->serializeStudent($student->fresh(['familyGroup', 'branch'])),
        ]);
    }

    public function store(StoreStudentRequest $request, StudentCreator $studentCreator): JsonResponse
    {
        $branch = $this->resolveWritableBranch($request, $request->integer('branch_id') ?: null);

        abort_unless($branch, 403);

        $student = $studentCreator->create(
            $branch,
            $request->safe()->except(['id_proof', 'photo', 'branch_id']),
            $request->file('photo'),
            $request->file('id_proof'),
        );

        $this->logActivity($request, 'student.created', "Created student {$student->student_code} ({$student->name}).", $student, $student->branch_id);

        return response()->json([
            'message' => "Student \"{$student->name}\" created.",
            'student' => $this->serializeStudent($student->load(['familyGroup', 'branch'])),
        ], 201);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorizeStudent($request, $student);
        $student->loadMissing(['familyGroup', 'branch']);

        $data = $request->safe()->except(['id_proof', 'photo', 'reset_app_login']);

        if ($request->hasFile('id_proof')) {
            if ($student->id_proof_path) {
                Storage::disk('local')->delete($student->id_proof_path);
            }

            $data['id_proof_path'] = $request->file('id_proof')->store('id-proofs/'.$student->branch_id, 'local');
        }

        if ($request->hasFile('photo')) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('student-photos/'.$student->branch_id, 'public');
        }

        if ($student->family_group_id && $student->is_family_primary) {
            $this->studentFamilyService->updateGroup($student->familyGroup, [
                'guardian_name' => $data['father_name'] ?? $student->father_name,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            unset($data['phone'], $data['email']);
        } elseif ($student->family_group_id) {
            unset($data['phone'], $data['email'], $data['father_name'], $data['address']);
        } else {
            $data['phone'] = filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null;
            $data['email'] = filled($data['email'] ?? null) ? strtolower(trim((string) $data['email'])) : null;
        }

        $student->update($data);

        if ($request->boolean('reset_app_login')) {
            $student->clearAppPin();
        }

        return response()->json([
            'message' => "Student \"{$student->name}\" updated.",
            'student' => $this->serializeStudent($student->fresh(['familyGroup', 'branch'])),
        ]);
    }

    public function idCard(Request $request, Student $student): View
    {
        $this->authorizeStudent($request, $student);
        $student->load('branch');

        return view('students.id-card', [
            'student' => $student,
            'branchName' => $student->branch?->display_name ?: $student->branch?->name,
        ]);
    }

    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorizeStudent($request, $student);

        $name = $student->name;
        $code = $student->student_code;
        $branchId = $student->branch_id;

        if ($student->id_proof_path) {
            Storage::disk('local')->delete($student->id_proof_path);
        }

        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $student->delete();
        $this->logActivity($request, 'student.deleted', "Deleted student {$code} ({$name}).", null, $branchId);

        return response()->json(['message' => "Student \"{$name}\" deleted."]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $students = $this->constrainByActiveBranch(Student::query(), $request)
            ->whereIn('id', $validated['ids'])
            ->get();

        $deleted = 0;

        foreach ($students as $student) {
            if ($student->id_proof_path) {
                Storage::disk('local')->delete($student->id_proof_path);
            }

            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }

            $student->delete();
            $deleted++;
        }

        return response()->json([
            'message' => "{$deleted} student(s) deleted.",
            'deleted' => $deleted,
        ]);
    }

    private function authorizeStudent(Request $request, Student $student): void
    {
        abort_unless($student->branch_id, 403);
        $this->assertCanAccessBranch($request, $student->branch_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStudent(Student $student, bool $detailed = false): array
    {
        $student->loadMissing(['branch:id,name', 'familyGroup']);

        $payload = [
            'id' => $student->id,
            'student_code' => $student->student_code,
            'name' => $student->name,
            'gender' => $student->gender,
            'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
            'father_name' => $student->effectiveGuardianName(),
            'phone' => $student->effectivePhone(),
            'email' => $student->effectiveEmail(),
            'address' => $student->effectiveAddress(),
            'id_proof_type' => $student->id_proof_type,
            'status' => $student->status,
            'student_type' => $student->student_type ?: Student::TYPE_REGULAR,
            'student_type_label' => $student->typeLabel(),
            'branch_id' => $student->branch_id,
            'branch_name' => $student->branch?->name,
            'family_group_id' => $student->family_group_id,
            'is_family_primary' => (bool) $student->is_family_primary,
            'is_in_family' => $student->isInFamily(),
            'has_id_proof' => (bool) $student->idProofUrl(),
            'has_photo' => (bool) $student->photoUrl(),
            'has_app_pin' => $student->hasAppPin(),
            'photo_url' => $student->photoUrl(),
            'id_proof_url' => $student->idProofUrl(),
            'initials' => $student->initials(),
            'created_at' => $student->created_at?->format('M d, Y'),
            'linked_siblings' => $student->linkedSiblings()->map(fn (Student $sibling) => [
                'id' => $sibling->id,
                'student_code' => $sibling->student_code,
                'name' => $sibling->name,
            ])->values()->all(),
            'family_group' => $student->familyGroup ? [
                'id' => $student->familyGroup->id,
                'guardian_name' => $student->familyGroup->guardian_name,
                'phone' => $student->familyGroup->phone,
                'email' => $student->familyGroup->email,
                'address' => $student->familyGroup->address,
            ] : null,
        ];

        if ($detailed) {
            $payload['updated_at'] = $student->updated_at?->format('M d, Y h:i A');
            $payload['date_of_birth_label'] = $student->date_of_birth?->format('M d, Y');
            $payload['gender_label'] = $student->gender ? ucfirst($student->gender) : null;
        }

        return $payload;
    }
}
