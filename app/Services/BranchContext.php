<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BranchContext
{
    public const ALL = 'all';

    public function viewingAll(?User $user, ?Request $request = null): bool
    {
        return (bool) $user?->isPlatformAdmin()
            && (string) $request?->session()->get('active_branch_id') === self::ALL;
    }

    public function optionalBranchId(User $user, ?Request $request = null): ?int
    {
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        if (! $user->isPlatformAdmin()) {
            abort(403, 'Your account is not assigned to a branch.');
        }

        if ($this->viewingAll($user, $request)) {
            return null;
        }

        $sessionBranchId = $request?->session()->get('active_branch_id');

        if ($sessionBranchId && $sessionBranchId !== self::ALL && Branch::query()->whereKey($sessionBranchId)->exists()) {
            return (int) $sessionBranchId;
        }

        $fallback = Branch::query()->orderBy('name')->value('id');

        if ($fallback) {
            $request?->session()->put('active_branch_id', $fallback);

            return (int) $fallback;
        }

        return null;
    }

    public function optionalBranch(User $user, ?Request $request = null): ?Branch
    {
        $id = $this->optionalBranchId($user, $request);

        return $id ? Branch::query()->find($id) : null;
    }

    public function branchId(User $user, ?Request $request = null): int
    {
        $id = $this->optionalBranchId($user, $request);

        if ($id) {
            return $id;
        }

        abort(422, 'Select a specific branch before continuing.');
    }

    public function branch(User $user, ?Request $request = null): Branch
    {
        return Branch::query()->findOrFail($this->branchId($user, $request));
    }

    public function canManageAllBranches(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function apply(Builder $query, User $user, Request $request, string $column = 'branch_id'): Builder
    {
        $id = $this->optionalBranchId($user, $request);

        if ($id) {
            $query->where($column, $id);
        }

        return $query;
    }

    public function applyToHalls(Builder $query, User $user, Request $request): Builder
    {
        $id = $this->optionalBranchId($user, $request);

        if ($id) {
            $query->whereHas('hall', fn (Builder $hallQuery) => $hallQuery->where('branch_id', $id));
        }

        return $query;
    }

    public function applyToSeatHalls(Builder $query, User $user, Request $request): Builder
    {
        $id = $this->optionalBranchId($user, $request);

        if ($id) {
            $query->whereHas('seat.hall', fn (Builder $hallQuery) => $hallQuery->where('branch_id', $id));
        }

        return $query;
    }
}
