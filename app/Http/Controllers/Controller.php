<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\ActivityLogger;
use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function viewingAllBranches(Request $request): bool
    {
        return app(BranchContext::class)->viewingAll($request->user(), $request);
    }

    protected function optionalActiveBranchId(Request $request): ?int
    {
        return app(BranchContext::class)->optionalBranchId($request->user(), $request);
    }

    protected function optionalActiveBranch(Request $request): ?Branch
    {
        return app(BranchContext::class)->optionalBranch($request->user(), $request);
    }

    protected function activeBranchId(Request $request): int
    {
        return app(BranchContext::class)->branchId($request->user(), $request);
    }

    protected function activeBranch(Request $request): Branch
    {
        return app(BranchContext::class)->branch($request->user(), $request);
    }

    protected function constrainByActiveBranch(Builder $query, Request $request, string $column = 'branch_id'): Builder
    {
        return app(BranchContext::class)->apply($query, $request->user(), $request, $column);
    }

    protected function constrainByActiveHallBranch(Builder $query, Request $request): Builder
    {
        return app(BranchContext::class)->applyToHalls($query, $request->user(), $request);
    }

    protected function constrainByActiveSeatHall(Builder $query, Request $request): Builder
    {
        return app(BranchContext::class)->applyToSeatHalls($query, $request->user(), $request);
    }

    protected function assertCanAccessBranch(Request $request, ?int $branchId): void
    {
        abort_unless($branchId, 403);

        $user = $request->user();

        if ($user?->branch_id) {
            abort_unless((int) $user->branch_id === (int) $branchId, 403);

            return;
        }

        abort_unless($user?->isPlatformAdmin(), 403);

        if ($this->viewingAllBranches($request)) {
            return;
        }

        abort_unless((int) $this->optionalActiveBranchId($request) === (int) $branchId, 403);
    }

    protected function resolveWritableBranch(Request $request, ?int $requestedBranchId = null): Branch
    {
        $user = $request->user();

        if ($user?->branch_id) {
            return Branch::query()->findOrFail((int) $user->branch_id);
        }

        abort_unless($user?->isPlatformAdmin(), 403);

        $branchId = $requestedBranchId ?: $this->optionalActiveBranchId($request);

        abort_unless($branchId, 422, 'Select a branch first.');
        $this->assertCanAccessBranch($request, $branchId);

        return Branch::query()->findOrFail($branchId);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected function logActivity(
        Request $request,
        string $action,
        string $description,
        ?Model $subject = null,
        ?int $branchId = null,
        array $properties = [],
    ): void {
        app(ActivityLogger::class)->record(
            $request->user(),
            $action,
            $description,
            $subject,
            $branchId ?? $this->optionalActiveBranchId($request),
            $properties,
            $request,
        );
    }
}
