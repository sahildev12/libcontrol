<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibraryRegistry;
use Illuminate\Http\JsonResponse;

class MobileLibraryResolverController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $libraryCode = preg_replace('/\D+/', '', trim($code)) ?? '';

        if ($libraryCode === '' || strlen($libraryCode) > 12) {
            abort(404, 'Library not found.');
        }

        $entry = LibraryRegistry::query()
            ->where('library_code', $libraryCode)
            ->first();

        if (! $entry) {
            abort(404, 'Library not found. Check the code with your library staff.');
        }

        return response()->json([
            'code' => $entry->library_code,
            'api_base_url' => $entry->apiBaseUrl(),
            'name' => $entry->client_name,
        ]);
    }
}
