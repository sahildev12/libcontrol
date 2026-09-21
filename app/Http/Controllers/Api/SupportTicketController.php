<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicensedDeployment;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function store(Request $request): JsonResponse
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

        $uuid = trim((string) ($payload['uuid'] ?? ''));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $domain = LicensedDeployment::normalizeDomain((string) ($payload['domain'] ?? ''));
        $reporterName = trim((string) ($payload['reporter_name'] ?? 'Library Admin'));
        $reporterEmail = trim((string) ($payload['reporter_email'] ?? ''));
        $libraryCode = trim((string) ($payload['library_code'] ?? ''));
        $libraryName = trim((string) ($payload['library_name'] ?? ''));
        $category = trim((string) ($payload['category'] ?? 'general'));
        $priority = trim((string) ($payload['priority'] ?? 'normal'));

        if ($uuid === '' || $subject === '' || $message === '' || $reporterEmail === '') {
            return response()->json(['message' => 'Missing required ticket fields.'], 422);
        }

        $licenseKeyHash = LicensedDeployment::isPlaceholderLicenseKey($licenseKey)
            ? LicensedDeployment::discoveryKeyHash()
            : LicensedDeployment::hashKey($licenseKey);

        $ticket = SupportTicket::query()->firstOrNew(['uuid' => $uuid]);
        $ticket->fill([
            'deployment_license_key_hash' => $licenseKeyHash,
            'deployment_domain' => $domain,
            'library_code' => $libraryCode !== '' ? $libraryCode : null,
            'library_name' => $libraryName !== '' ? $libraryName : null,
            'subject' => $subject,
            'message' => $message,
            'category' => in_array($category, ['general', 'billing', 'technical', 'feature'], true) ? $category : 'general',
            'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
            'status' => $ticket->exists ? $ticket->status : SupportTicket::STATUS_OPEN,
            'reporter_name' => $reporterName,
            'reporter_email' => $reporterEmail,
            'synced_at' => now(),
        ]);
        $ticket->save();

        return response()->json([
            'id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'status' => $ticket->status,
        ]);
    }
}
