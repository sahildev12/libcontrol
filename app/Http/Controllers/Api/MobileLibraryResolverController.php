<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibraryRegistry;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

class MobileLibraryResolverController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $libraryCode = preg_replace('/\D+/', '', trim($code)) ?? '';

        if ($libraryCode === '' || strlen($libraryCode) > 12) {
            abort(404, 'Library not found.');
        }

        if (config('libcontrol.license_server.enabled')) {
            return $this->fromRegistry($libraryCode);
        }

        return $this->fromPlatformSettings($libraryCode);
    }

    private function fromRegistry(string $libraryCode): JsonResponse
    {
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

    private function fromPlatformSettings(string $libraryCode): JsonResponse
    {
        $settings = PlatformSetting::current();
        $ownCode = preg_replace('/\D+/', '', (string) $settings->library_code) ?? '';

        if ($ownCode === '' || $ownCode !== $libraryCode) {
            abort(404, 'Library not found. Check the code with your library staff.');
        }

        return response()->json([
            'code' => $ownCode,
            'api_base_url' => rtrim((string) config('app.url'), '/'),
            'name' => $settings->displayName(),
        ]);
    }
}
