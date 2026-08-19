<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\BranchContext;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function activeBranchId(Request $request): int
    {
        return app(BranchContext::class)->branchId($request->user(), $request);
    }

    protected function activeBranch(Request $request): Branch
    {
        return app(BranchContext::class)->branch($request->user(), $request);
    }
}
