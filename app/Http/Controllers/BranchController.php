<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlatformBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $branches = Branch::query()
            ->with('users:id,branch_id,email')
            ->withCount(['users', 'halls', 'students'])
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch) => $this->serializeBranchRow($branch));

        return view('branch.index', compact('branches'));
    }

    public function show(Request $request, Branch $branch): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $branch->load(['users:id,branch_id,email']);
        $branch->loadCount(['users', 'halls', 'students']);

        $halls = $branch->halls()
            ->withCount([
                'seats',
                'seats as filled_seats_count' => fn ($query) => $query->whereHas(
                    'bookings',
                    fn ($bookingQuery) => $bookingQuery
                        ->whereNull('cancelled_at')
                        ->where('status', '!=', 'cancelled'),
                ),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($hall) => [
                'id' => $hall->id,
                'branch_id' => $hall->branch_id,
                'name' => $hall->name,
                'description' => $hall->description,
                'seat_capacity' => $hall->seat_capacity,
                'filled_seats_count' => $hall->filled_seats_count,
                'has_assigned_students' => $hall->hasAssignedStudents(),
                'min_seat_capacity' => $hall->minimumSeatCapacity(),
                'created_at' => $hall->created_at?->format('M d, Y'),
            ]);

        return response()->json([
            ...$this->serializeBranchRow($branch),
            'halls' => $halls,
        ]);
    }

    public function store(StorePlatformBranchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $branch = Branch::create([
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        $this->syncBranchLogin($branch, $validated, true);

        $this->logActivity($request, 'branch.created', "Created branch \"{$branch->name}\".", $branch, $branch->id);

        $request->session()->put('active_branch_id', $branch->id);

        return response()->json([
            'message' => "Branch \"{$branch->name}\" created.",
            'branch' => $this->serializeBranchRow($branch->load(['users:id,branch_id,email'])->loadCount(['users', 'halls', 'students'])),
        ], 201);
    }

    public function update(UpdateBranchRequest $request): JsonResponse
    {
        /** @var Branch $branch */
        $branch = $this->activeBranch($request);

        abort_unless($branch, 403);

        $branch->update(collect($request->validated())->except(['password'])->all());

        return response()->json([
            'message' => 'Branch details updated.',
            'branch' => $branch->fresh(),
        ]);
    }

    public function updateManaged(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $validated = $request->validated();
        $branch->update(collect($validated)->except(['password'])->all());
        $this->syncBranchLogin($branch->fresh(), $validated, false);

        return response()->json([
            'message' => 'Branch details updated.',
            'branch' => $this->serializeBranchRow($branch->fresh()->load(['users:id,branch_id,email'])->loadCount(['users', 'halls', 'students'])),
        ]);
    }

    public function resetPassword(Request $request, Branch $branch): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $user = $branch->users()->orderBy('id')->first();

        abort_unless($user, 422, 'This branch does not have a login user yet.');

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $password = $validated['password'] ?? Str::password(12);
        $user->update(['password' => $password]);

        return response()->json([
            'message' => 'Password reset. Copy it now — it will not be shown again.',
            'login_email' => $user->email,
            'password' => $password,
        ]);
    }

    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        if (Branch::query()->count() <= 1) {
            return response()->json([
                'message' => 'At least one branch must remain.',
            ], 422);
        }

        $name = $branch->name;
        $branchId = $branch->id;
        $branch->delete();
        $this->logActivity($request, 'branch.deleted', "Deleted branch \"{$name}\".", null, null, ['branch_id' => $branchId]);

        if ((int) $request->session()->get('active_branch_id') === $branchId) {
            $nextBranchId = Branch::query()->orderBy('name')->value('id');

            if ($nextBranchId) {
                $request->session()->put('active_branch_id', $nextBranchId);
            } else {
                $request->session()->forget('active_branch_id');
            }
        }

        return response()->json([
            'message' => "Branch \"{$name}\" deleted.",
        ]);
    }

    public function switchBranch(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isPlatformAdmin(), 403);

        $validated = $request->validate([
            'branch_id' => ['required'],
        ]);

        if ((string) $validated['branch_id'] === 'all') {
            $request->session()->put('active_branch_id', 'all');
            $this->logActivity($request, 'branch.switched', 'Switched view to all branches.', null, null);

            return response()->json([
                'message' => 'Now viewing all branches.',
                'branch' => ['id' => 'all', 'name' => 'All branches'],
            ]);
        }

        $request->validate([
            'branch_id' => ['integer', 'exists:branches,id'],
        ]);

        $request->session()->put('active_branch_id', (int) $validated['branch_id']);

        $branch = Branch::query()->findOrFail((int) $validated['branch_id']);
        $this->logActivity($request, 'branch.switched', "Switched active branch to {$branch->name}.", $branch, $branch->id);

        return response()->json([
            'message' => "Active branch switched to {$branch->name}.",
            'branch' => ['id' => $branch->id, 'name' => $branch->name],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeBranchRow(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'contact_person' => $branch->contact_person,
            'phone' => $branch->phone,
            'email' => $branch->email ?: $branch->users->sortBy('id')->first()?->email,
            'login_email' => $branch->users->sortBy('id')->first()?->email ?: $branch->email,
            'address' => $branch->address,
            'users_count' => $branch->users_count ?? $branch->users()->count(),
            'halls_count' => $branch->halls_count ?? $branch->halls()->count(),
            'students_count' => $branch->students_count ?? $branch->students()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncBranchLogin(Branch $branch, array $validated, bool $requirePassword): void
    {
        $loginEmail = $validated['email'] ?? $validated['login_email'] ?? null;

        if (! $loginEmail && ! $requirePassword) {
            return;
        }

        $user = $branch->users()->orderBy('id')->first();
        $payload = [
            'branch_id' => $branch->id,
            'name' => $validated['contact_person'] ?: $branch->name.' Admin',
        ];

        if ($loginEmail) {
            $payload['email'] = $loginEmail;
        }

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        if ($user) {
            $user->update($payload);

            return;
        }

        User::query()->create([
            ...$payload,
            'email' => $loginEmail,
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);
    }
}
