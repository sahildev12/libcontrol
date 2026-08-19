<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Services\StudentCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = Student::query()
            ->where('branch_id', $this->activeBranchId($request))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Student $student) => $this->serializeStudent($student));

        return view('students.index', compact('students'));
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

    public function store(StoreStudentRequest $request, StudentCreator $studentCreator): JsonResponse
    {
        $branch = $this->activeBranch($request);

        abort_unless($branch, 403);

        $student = $studentCreator->create(
            $branch,
            $request->safe()->except(['id_proof', 'photo']),
            $request->file('photo'),
            $request->file('id_proof'),
        );

        return response()->json([
            'message' => "Student \"{$student->name}\" created.",
            'student' => $this->serializeStudent($student),
        ], 201);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorizeStudent($request, $student);

        $data = $request->safe()->except(['id_proof', 'photo']);

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

        $student->update($data);

        return response()->json([
            'message' => "Student \"{$student->name}\" updated.",
            'student' => $this->serializeStudent($student->fresh()),
        ]);
    }

    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorizeStudent($request, $student);

        $name = $student->name;

        if ($student->id_proof_path) {
            Storage::disk('local')->delete($student->id_proof_path);
        }

        if ($student->photo_path) {
            Storage::disk('public')->delete($student->photo_path);
        }

        $student->delete();

        return response()->json(['message' => "Student \"{$name}\" deleted."]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $students = Student::query()
            ->where('branch_id', $this->activeBranchId($request))
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
        abort_unless($student->branch_id === $this->activeBranchId($request), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStudent(Student $student, bool $detailed = false): array
    {
        $payload = [
            'id' => $student->id,
            'student_code' => $student->student_code,
            'name' => $student->name,
            'gender' => $student->gender,
            'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
            'father_name' => $student->father_name,
            'phone' => $student->phone,
            'email' => $student->email,
            'address' => $student->address,
            'id_proof_type' => $student->id_proof_type,
            'status' => $student->status,
            'student_type' => $student->student_type ?: Student::TYPE_REGULAR,
            'student_type_label' => $student->typeLabel(),
            'has_id_proof' => (bool) $student->idProofUrl(),
            'has_photo' => (bool) $student->photoUrl(),
            'photo_url' => $student->photoUrl(),
            'id_proof_url' => $student->idProofUrl(),
            'initials' => $student->initials(),
            'created_at' => $student->created_at?->format('M d, Y'),
        ];

        if ($detailed) {
            $payload['updated_at'] = $student->updated_at?->format('M d, Y h:i A');
            $payload['date_of_birth_label'] = $student->date_of_birth?->format('M d, Y');
            $payload['gender_label'] = $student->gender ? ucfirst($student->gender) : null;
        }

        return $payload;
    }
}
