<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Http\Requests\UpdateEnquiryRequest;
use App\Models\Enquiry;
use App\Services\Growth\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function __construct()
    {
        abort_unless(config('libcontrol.modules.enquiries'), 404);
    }

    public function index(Request $request): View
    {
        $enquiries = $this->constrainByActiveBranch(Enquiry::query(), $request)
            ->with(['student:id,student_code,name', 'branch:id,name'])
            ->when(app(ReferralService::class)->tableReady(), fn ($query) => $query->with('referral.referrer:id,name,student_code'))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Enquiry $enquiry) => $this->serializeEnquiry($enquiry));

        $viewingAll = $this->viewingAllBranches($request);
        $branches = $request->user()?->isPlatformAdmin()
            ? \App\Models\Branch::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $defaultBranchId = $this->optionalActiveBranchId($request);

        $statuses = Enquiry::STATUSES;
        $followUpStatuses = Enquiry::FOLLOW_UP_STATUSES;

        return view('enquiries.index', compact('enquiries', 'viewingAll', 'branches', 'defaultBranchId', 'statuses', 'followUpStatuses'));
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
        $wasConverted = $enquiry->status === Enquiry::STATUS_CONVERTED;

        $enquiry->update($validated);

        if (! $wasConverted && $enquiry->status === Enquiry::STATUS_CONVERTED) {
            app(ReferralService::class)->markEnquiryConverted($enquiry);
        }

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

    public function convert(Request $request, Enquiry $enquiry): JsonResponse
    {
        $this->authorizeEnquiry($request, $enquiry);

        abort_if($enquiry->status === Enquiry::STATUS_CONVERTED, 422, 'Enquiry is already converted.');

        $enquiry->update(['status' => Enquiry::STATUS_CONVERTED]);
        app(ReferralService::class)->markEnquiryConverted($enquiry);
        $this->logActivity($request, 'enquiry.converted', "Marked enquiry for {$enquiry->name} as converted.", $enquiry, $enquiry->branch_id);

        return response()->json([
            'message' => 'Enquiry marked as converted.',
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
        $referrer = app(ReferralService::class)->tableReady()
            ? $enquiry->loadMissing('referral.referrer:id,name,student_code')->referral?->referrer
            : null;

        return [
            'id' => $enquiry->id,
            'name' => $enquiry->name,
            'phone' => $enquiry->phone,
            'email' => $enquiry->email,
            'message' => $enquiry->message,
            'status' => $enquiry->status,
            'status_label' => Enquiry::statusLabel($enquiry->status),
            'follow_up_date' => $enquiry->follow_up_date?->format('Y-m-d'),
            'follow_up_date_label' => $enquiry->follow_up_date?->format('d M Y'),
            'follow_up_note' => $enquiry->follow_up_note,
            'student_id' => $enquiry->student_id,
            'student_code' => $enquiry->student?->student_code,
            'student_name' => $enquiry->student?->name,
            'referred_by' => $referrer ? "{$referrer->name} ({$referrer->student_code})" : null,
            'branch_id' => $enquiry->branch_id,
            'branch_name' => $enquiry->branch?->name,
            'created_at' => $enquiry->created_at?->format('M d, Y'),
            'created_month' => $enquiry->created_at?->format('Y-m'),
        ];
    }
}
