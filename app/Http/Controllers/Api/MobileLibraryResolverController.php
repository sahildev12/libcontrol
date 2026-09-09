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

        return response()->json($entry->mobileResolverPayload());
    }

    private function fromPlatformSettings(string $libraryCode): JsonResponse
    {
        $settings = PlatformSetting::current();
        $ownCode = preg_replace('/\D+/', '', (string) $settings->library_code) ?? '';

        if ($ownCode === '' || $ownCode !== $libraryCode) {
            abort(404, 'Library not found. Check the code with your library staff.');
        }

        $public = trim((string) config('libcontrol.deployment.public_url', ''));
        $appUrl = $public !== '' ? rtrim($public, '/') : rtrim((string) config('app.url'), '/');
        $prefix = strtoupper(trim((string) $settings->student_code_prefix));
        $padding = max(1, min(6, (int) ($settings->student_code_padding
            ?: config('libcontrol.defaults.student_code_padding', 3))));

        return response()->json([
            'code' => $ownCode,
            'api_base_url' => $appUrl,
            'name' => $settings->displayName(),
            'student_code_prefix' => $prefix !== '' ? $prefix : null,
            'student_code_padding' => $padding,
            'sample_student_code' => $prefix !== ''
                ? sprintf('%s-%0'.$padding.'d', $prefix, 1)
                : null,
        ]);
    }
}
