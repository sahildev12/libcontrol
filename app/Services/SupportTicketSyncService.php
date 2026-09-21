<?php

namespace App\Services;

use App\Models\LicensedDeployment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Runtime\SyncCoordinator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SupportTicketSyncService
{
    public function __construct(
        private SyncCoordinator $syncCoordinator,
    ) {}

    /**
     * @param  array{
     *     uuid: string,
     *     subject: string,
     *     message: string,
     *     category: string,
     *     priority: string,
     *     reporter_name: string,
     *     reporter_email: string,
     *     library_code?: string|null,
     *     library_name?: string|null,
     *     created_at?: string|null,
     * }  $ticketData
     */
    public function submit(array $ticketData): SupportTicketSyncResult
    {
        if (config('libcontrol.license_server.enabled')) {
            return SupportTicketSyncResult::failure('Support tickets are submitted from client libraries, not the Phenomit hub.');
        }

        $licenseKey = trim((string) config('libcontrol.deployment.license_key'));

        if (LicensedDeployment::isPlaceholderLicenseKey($licenseKey)) {
            return SupportTicketSyncResult::failure(
                'Support tickets require a real license key. Set LIBCONTROL_LICENSE_KEY in this installation\'s .env file (copy it from Dev & Domains on libcontrol.phenomit.com).',
            );
        }

        $signingKey = $licenseKey;
        $settings = \App\Models\PlatformSetting::current();

        $payload = [
            'uuid' => $ticketData['uuid'],
            'domain' => $this->currentDomain(),
            'app_url' => $this->syncAppUrl(),
            'fingerprint' => $this->syncCoordinator->fingerprint(),
            'library_code' => $ticketData['library_code'] ?? $settings->library_code,
            'library_name' => $ticketData['library_name'] ?? $settings->displayName(),
            'subject' => $ticketData['subject'],
            'message' => $ticketData['message'],
            'category' => $ticketData['category'],
            'priority' => $ticketData['priority'],
            'reporter_name' => $ticketData['reporter_name'],
            'reporter_email' => $ticketData['reporter_email'],
            'created_at' => $ticketData['created_at'] ?? now()->toIso8601String(),
        ];

        $endpoint = $this->supportEndpoint();
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $token = hash_hmac('sha256', $body, $signingKey);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Sync-Token' => $token,
                    'X-License-Key' => $licenseKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint);

            if (! $response->successful()) {
                Log::warning('Support ticket sync failed.', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'domain' => $payload['domain'],
                ]);

                return SupportTicketSyncResult::failure($this->failureMessage($response->status(), $endpoint));
            }

            $data = $response->json();

            if (! is_array($data) || ! isset($data['id'])) {
                Log::warning('Support ticket sync returned an unexpected response.', [
                    'endpoint' => $endpoint,
                    'body' => $response->body(),
                ]);

                return SupportTicketSyncResult::failure('Phenomit accepted the request but returned an invalid response. Contact support.');
            }

            return SupportTicketSyncResult::success((int) $data['id']);
        } catch (\Throwable $e) {
            Log::error('Support ticket sync connection error.', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
                'domain' => $payload['domain'] ?? null,
            ]);

            return SupportTicketSyncResult::failure(
                'Could not reach Phenomit support server at '.$endpoint.'. Check LIBCONTROL_SYNC_ENDPOINT and that libcontrol.phenomit.com is online.',
            );
        }
    }

    public function pullUpdates(?User $user = null): int
    {
        if (config('libcontrol.license_server.enabled')) {
            return 0;
        }

        if (! Schema::hasTable('support_tickets')) {
            return 0;
        }

        $licenseKey = trim((string) config('libcontrol.deployment.license_key'));

        if (LicensedDeployment::isPlaceholderLicenseKey($licenseKey)) {
            return 0;
        }

        $query = SupportTicket::query()->whereNotNull('remote_id');

        if ($user && ! $user->isAnyAdmin()) {
            $query->where('reporter_user_id', $user->id);
        }

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            return 0;
        }

        $payload = [
            'domain' => $this->currentDomain(),
            'uuids' => $tickets->pluck('uuid')->filter()->values()->all(),
        ];

        $endpoint = $this->supportPullEndpoint();
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $token = hash_hmac('sha256', $body, $licenseKey);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Sync-Token' => $token,
                    'X-License-Key' => $licenseKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint);

            if (! $response->successful()) {
                Log::warning('Support ticket pull failed.', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 0;
            }

            $data = $response->json();

            if (! is_array($data) || ! is_array($data['tickets'] ?? null)) {
                return 0;
            }

            $updated = 0;

            foreach ($data['tickets'] as $remoteTicket) {
                if (! is_array($remoteTicket)) {
                    continue;
                }

                $uuid = trim((string) ($remoteTicket['uuid'] ?? ''));
                $localTicket = $tickets->firstWhere('uuid', $uuid);

                if (! $localTicket) {
                    continue;
                }

                $remoteStatus = trim((string) ($remoteTicket['status'] ?? $localTicket->status));
                $remoteNotes = (string) ($remoteTicket['admin_notes'] ?? '');

                if (! in_array($remoteStatus, [
                    SupportTicket::STATUS_OPEN,
                    SupportTicket::STATUS_IN_PROGRESS,
                    SupportTicket::STATUS_RESOLVED,
                    SupportTicket::STATUS_CLOSED,
                ], true)) {
                    $remoteStatus = $localTicket->status;
                }

                $statusChanged = $localTicket->status !== $remoteStatus;
                $notesChanged = (string) $localTicket->admin_notes !== $remoteNotes;

                if (! $statusChanged && ! $notesChanged) {
                    continue;
                }

                $localTicket->fill([
                    'status' => $remoteStatus,
                    'admin_notes' => $remoteNotes !== '' ? $remoteNotes : null,
                    'client_update_pending' => true,
                ]);

                $localTicket->save();
                $updated++;
            }

            return $updated;
        } catch (\Throwable $e) {
            Log::warning('Support ticket pull connection error.', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function push(SupportTicket $ticket): SupportTicketSyncResult
    {
        $result = $this->submit([
            'uuid' => $ticket->uuid,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'reporter_name' => $ticket->reporter_name,
            'reporter_email' => $ticket->reporter_email,
            'library_code' => $ticket->library_code,
            'library_name' => $ticket->library_name,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ]);

        if ($result->ok) {
            $ticket->update([
                'remote_id' => $result->remoteId,
                'synced_at' => now(),
            ]);
        }

        return $result;
    }

    private function failureMessage(int $status, string $endpoint): string
    {
        return match ($status) {
            401 => 'Phenomit rejected this ticket (unauthorized). Verify LIBCONTROL_LICENSE_KEY matches the deployment on libcontrol.phenomit.com.',
            404 => 'Support API not found at '.$endpoint.'. On libcontrol.phenomit.com set LIBCONTROL_LICENSE_SERVER=true and run php artisan migrate --force.',
            422 => 'Phenomit rejected the ticket data. Check subject, message, and reporter email.',
            429 => 'Too many support requests. Please wait a few minutes and try again.',
            default => 'Phenomit could not accept this ticket (HTTP '.$status.').',
        };
    }

    public function supportEndpoint(): string
    {
        $override = config('libcontrol.deployment.support_endpoint');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        $syncEndpoint = trim((string) config('libcontrol.deployment.sync_endpoint', ''));
        if ($syncEndpoint !== '') {
            return str_replace('/api/runtime/sync', '/api/support/tickets', $syncEndpoint);
        }

        $encoded = (string) config('libcontrol.deployment.sync_endpoint_encoded', '');
        if ($encoded !== '') {
            $decoded = base64_decode($encoded, true);
            if (is_string($decoded) && $decoded !== '') {
                return str_replace('/api/runtime/sync', '/api/support/tickets', $decoded);
            }
        }

        return rtrim((string) config('app.url'), '/').'/api/support/tickets';
    }

    public function supportPullEndpoint(): string
    {
        return str_replace('/api/support/tickets', '/api/support/tickets/pull', $this->supportEndpoint());
    }

    private function currentDomain(): string
    {
        $host = request()->getHost();

        if ($host !== '') {
            return LicensedDeployment::normalizeDomain($host);
        }

        return LicensedDeployment::normalizeDomain((string) config('app.url'));
    }

    private function syncAppUrl(): string
    {
        $public = trim((string) config('libcontrol.deployment.public_url', ''));

        return $public !== '' ? rtrim($public, '/') : rtrim((string) config('app.url'), '/');
    }
}
