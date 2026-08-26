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
        $enquiries = $this->constrainByActiveBranch(Enquiry::query(), $request)
            ->with(['student:id,student_code,name', 'branch:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Enquiry $enquiry) => $this->serializeEnquiry($enquiry));

        $viewingAll = $this->viewingAllBranches($request);
        $branches = $request->user()?->isPlatformAdmin()
            ? \App\Models\Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $defaultBranchId = $this->optionalActiveBranchId($request);

        return view('enquiries.index', compact('enquiries', 'viewingAll', 'branches', 'defaultBranchId'));
    }

    public function store(StoreEnquiryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $branch = $this->resolveWritableBranch($request, isset($validated['branch_id']) ? (int) $validated['branch_id'] : null);

        $enquiry = Enquiry::create([
            ...collect($validated)->except('branch_id')->all(),
            'branch_id' => $branch->id,
            'status' => $validated['status'] ?? 'new',
        ]);
        $this->logActivity($request, 'enquiry.created', "Added enquiry for {$enquiry->name}.", $enquiry, $enquiry->branch_id);

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

        $name = $enquiry->name;
        $branchId = $enquiry->branch_id;
        $enquiry->delete();
        $this->logActivity($request, 'enquiry.deleted', "Deleted enquiry for {$name}.", null, $branchId);

        return response()->json(['message' => 'Enquiry deleted.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $enquiries = $this->constrainByActiveBranch(Enquiry::query(), $request)
            ->whereIn('id', $validated['ids'])
            ->get();

        $count = $enquiries->count();
        Enquiry::query()->whereIn('id', $enquiries->modelKeys())->delete();
        $this->logActivity($request, 'enquiry.bulk_deleted', "Deleted {$count} enquiry(ies).");

        return response()->json([
            'message' => "{$count} enquiry(ies) deleted.",
            'deleted' => $count,
        ]);
    }

    public function convert(Request $request, Enquiry $enquiry, StudentCodeService $studentCodeService): JsonResponse
    {
        $this->authorizeEnquiry($request, $enquiry);

        abort_if($enquiry->student_id, 422, 'Enquiry is already converted.');

        $branch = \App\Models\Branch::query()->findOrFail($enquiry->branch_id);
        $this->assertCanAccessBranch($request, $branch->id);

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
        $this->assertCanAccessBranch($request, $enquiry->branch_id);
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
            'branch_id' => $enquiry->branch_id,
            'branch_name' => $enquiry->branch?->name,
            'created_at' => $enquiry->created_at?->format('M d, Y'),
        ];
    }
}
