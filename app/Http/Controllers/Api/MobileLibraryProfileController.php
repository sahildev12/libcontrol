<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BranchStudentCodePrefixService;
use Illuminate\Http\JsonResponse;

class MobileLibraryProfileController extends Controller
{
    public function styles(BranchStudentCodePrefixService $prefixes): JsonResponse
    {
        $styles = $prefixes->stylesForLibrary();
        $uniquePrefixes = collect($styles)->pluck('prefix')->unique()->values();

        return response()->json([
            'multi_branch_prefixes' => $uniquePrefixes->count() > 1,
            'branch_styles' => $styles,
        ]);
    }
}
