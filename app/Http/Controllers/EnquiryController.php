<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Http\Requests\UpdateEnquiryRequest;
use App\Models\Enquiry;
use App\Models\Student;
use App\Services\StudentCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $enquiries = Enquiry::query()
            ->where('branch_id', $this->activeBranchId($request))
            ->with('student:id,student_code,name')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Enquiry $enquiry) => $this->serializeEnquiry($enquiry));

        return view('enquiries.index', compact('enquiries'));
    }

    public function store(StoreEnquiryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $enquiry = Enquiry::create([
            ...$validated,
            'branch_id' => $this->activeBranchId($request),
            'status' => $validated['status'] ?? 'new',
        ]);

        return response()->json([
            'message' => 'Enquiry added.',
            'enquiry' => $this->serializeEnquiry($enquiry),
        ], 201);
    }

    public function update(UpdateEnquiryRequest $request, Enquiry $enquiry): JsonResponse
    {
        $this->authorizeEnquiry($request, $enquiry);

        $validated = $request->validated();

        $enquiry->update($validated);

        return response()->json([
            'message' => 'Enquiry updated.',
            'enquiry' => $this->serializeEnquiry($enquiry->fresh()->load('student:id,student_code,name')),
        ]);
    }

    public function destroy(Request $request, Enquiry $enquiry): JsonResponse
    {
        $this->authorizeEnquiry($request, $enquiry);

        $enquiry->delete();

        return response()->json(['message' => 'Enquiry deleted.']);
    }

    public function convert(Request $request, Enquiry $enquiry, StudentCodeService $studentCodeService): JsonResponse
    {
        $this->authorizeEnquiry($request, $enquiry);

        abort_if($enquiry->student_id, 422, 'Enquiry is already converted.');

        $branch = $this->activeBranch($request);
        abort_unless($branch, 403);

        $student = Student::create([
            'branch_id' => $branch->id,
            'student_code' => $studentCodeService->generate($branch),
            'name' => $enquiry->name,
            'phone' => $enquiry->phone,
            'email' => $enquiry->email,
            'status' => 'active',
        ]);

        $enquiry->update([
            'status' => 'converted',
            'student_id' => $student->id,
        ]);

        return response()->json([
            'message' => "Enquiry converted to student {$student->student_code}.",
            'enquiry' => $this->serializeEnquiry($enquiry->fresh()->load('student:id,student_code,name')),
        ]);
    }

    private function authorizeEnquiry(Request $request, Enquiry $enquiry): void
    {
        abort_unless($enquiry->branch_id === $this->activeBranchId($request), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEnquiry(Enquiry $enquiry): array
    {
        return [
            'id' => $enquiry->id,
            'name' => $enquiry->name,
            'phone' => $enquiry->phone,
            'email' => $enquiry->email,
            'message' => $enquiry->message,
            'status' => $enquiry->status,
            'student_id' => $enquiry->student_id,
            'student_code' => $enquiry->student?->student_code,
            'student_name' => $enquiry->student?->name,
            'created_at' => $enquiry->created_at?->format('M d, Y'),
        ];
    }
}
