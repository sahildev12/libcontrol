<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibraryRegistry;
use Illuminate\Http\JsonResponse;

class MobileLibraryResolverController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $prefix = strtoupper(trim($code));

        if ($prefix === '' || strlen($prefix) > 20) {
            abort(404, 'Library not found.');
        }

        $entry = LibraryRegistry::query()
            ->where('student_code_prefix', $prefix)
            ->first();

        if (! $entry) {
            abort(404, 'Library not found. Check the code with your library staff.');
        }

        return response()->json([
            'code' => $entry->student_code_prefix,
            'api_base_url' => $entry->apiBaseUrl(),
            'name' => $entry->client_name,
        ]);
    }
}
