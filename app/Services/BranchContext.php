<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchContext
{
    public function branchId(User $user, ?Request $request = null): int
    {
        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        if ($user->isPlatformAdmin()) {
            $sessionBranchId = $request?->session()->get('active_branch_id');

            if ($sessionBranchId && Branch::query()->whereKey($sessionBranchId)->exists()) {
                return (int) $sessionBranchId;
            }

            $fallback = Branch::query()->orderBy('name')->value('id');

            if ($fallback) {
                $request?->session()->put('active_branch_id', $fallback);

                return (int) $fallback;
            }

            abort(403, 'No branches exist yet. Create a branch first.');
        }

        abort(403, 'Your account is not assigned to a branch.');
    }

    public function branch(User $user, ?Request $request = null): Branch
    {
        return Branch::query()->findOrFail($this->branchId($user, $request));
    }

    public function canManageAllBranches(User $user): bool
    {
        return $user->isPlatformAdmin();
    }
}
