<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthenticatesSupportTicketRequests;
use App\Http\Controllers\Controller;
use App\Models\GrowthOrder;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    use AuthenticatesSupportTicketRequests;

    public function store(Request $request): JsonResponse
    {
        $auth = $this->authenticateSupportTicketRequest($request);

        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $payload = $auth['payload'];
        $licenseKeyHash = $auth['license_key_hash'];

        $uuid = trim((string) ($payload['uuid'] ?? ''));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $domain = \App\Models\LicensedDeployment::normalizeDomain((string) ($payload['domain'] ?? ''));
        $reporterName = trim((string) ($payload['reporter_name'] ?? 'Library Admin'));
        $reporterEmail = trim((string) ($payload['reporter_email'] ?? ''));
        $libraryCode = trim((string) ($payload['library_code'] ?? ''));
        $libraryName = trim((string) ($payload['library_name'] ?? ''));
        $category = trim((string) ($payload['category'] ?? 'general'));
        $priority = trim((string) ($payload['priority'] ?? 'normal'));

        if ($uuid === '' || $subject === '' || $message === '' || $reporterEmail === '') {
            return response()->json(['message' => 'Missing required ticket fields.'], 422);
        }

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

        $this->mirrorGrowthOrderFromTicket($ticket, $licenseKeyHash, $domain);

        return response()->json([
            'id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'status' => $ticket->status,
        ]);
    }

    private function mirrorGrowthOrderFromTicket(SupportTicket $ticket, string $licenseKeyHash, string $domain): void
    {
        if (! Schema::hasTable('growth_orders')) {
            return;
        }

        if (! str_starts_with($ticket->subject, 'Growth order:')) {
            return;
        }

        $itemName = trim(Str::after($ticket->subject, 'Growth order:')) ?: $ticket->subject;
        $itemKey = 'unknown';
        $orderType = 'package';
        $orderUuid = $ticket->uuid;

        if (preg_match('/Item:\s*(.+?)\s*\(([^)]+)\)/i', (string) $ticket->message, $m)) {
            $itemName = trim($m[1]);
            $itemKey = trim($m[2]);
        }
        if (preg_match('/Type:\s*(package|service)/i', (string) $ticket->message, $m)) {
            $orderType = strtolower($m[1]);
        }
        if (preg_match('/Order UUID:\s*([0-9a-f-]{36})/i', (string) $ticket->message, $m)) {
            $orderUuid = $m[1];
        }

        GrowthOrder::query()->updateOrCreate(
            ['uuid' => $orderUuid],
            [
                'order_type' => $orderType,
                'item_key' => $itemKey,
                'item_name' => $itemName,
                'status' => GrowthOrder::STATUS_NEW,
                'message' => $ticket->message,
                'contact_name' => $ticket->reporter_name,
                'contact_email' => $ticket->reporter_email,
                'library_name' => $ticket->library_name,
                'library_code' => $ticket->library_code,
                'deployment_domain' => $domain !== '' ? $domain : $ticket->deployment_domain,
                'deployment_license_key_hash' => $licenseKeyHash,
                'support_ticket_id' => $ticket->id,
            ]
        );
    }

    public function pull(Request $request): JsonResponse
    {
        $auth = $this->authenticateSupportTicketRequest($request);

        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        $payload = $auth['payload'];
        $licenseKeyHash = $auth['license_key_hash'];
        $uuids = array_values(array_filter(array_map(
            fn ($uuid) => trim((string) $uuid),
            is_array($payload['uuids'] ?? null) ? $payload['uuids'] : [],
        )));

        $query = SupportTicket::query()
            ->where('deployment_license_key_hash', $licenseKeyHash)
            ->orderByDesc('updated_at');

        if ($uuids !== []) {
            $query->whereIn('uuid', $uuids);
        }

        $tickets = $query->get(['uuid', 'status', 'admin_notes', 'updated_at']);

        return response()->json([
            'tickets' => $tickets->map(fn (SupportTicket $ticket) => [
                'uuid' => $ticket->uuid,
                'status' => $ticket->status,
                'admin_notes' => $ticket->admin_notes,
                'updated_at' => $ticket->updated_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }
}
