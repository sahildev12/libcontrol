<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\LicensedDeployment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait AuthenticatesSupportTicketRequests
{
    /**
     * @return array{payload: array<string, mixed>, license_key_hash: string}|JsonResponse
     */
    protected function authenticateSupportTicketRequest(Request $request): array|JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid payload.'], 422);
        }

        $licenseKey = trim((string) $request->header('X-License-Key', ''));
        $token = (string) $request->header('X-Sync-Token', '');

        if ($token === '') {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $signingKey = LicensedDeployment::isPlaceholderLicenseKey($licenseKey)
            ? (string) config('libcontrol.discovery.secret')
            : $licenseKey;

        $expected = hash_hmac('sha256', $rawBody, $signingKey);

        if (! hash_equals($expected, $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $licenseKeyHash = LicensedDeployment::isPlaceholderLicenseKey($licenseKey)
            ? LicensedDeployment::discoveryKeyHash()
            : LicensedDeployment::hashKey($licenseKey);

        return [
            'payload' => $payload,
            'license_key_hash' => $licenseKeyHash,
        ];
    }
}
