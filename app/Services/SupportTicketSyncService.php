<?php

namespace App\Services;

use App\Models\LicensedDeployment;
use App\Models\SupportTicket;
use App\Support\Runtime\SyncCoordinator;
use Illuminate\Support\Facades\Http;

class SupportTicketSyncService
{
    public function __construct(
        private SyncCoordinator $syncCoordinator,
    ) {}

    public function push(SupportTicket $ticket): bool
    {
        if (config('libcontrol.license_server.enabled')) {
            return true;
        }

        $licenseKey = trim((string) config('libcontrol.deployment.license_key'));
        $signingKey = LicensedDeployment::isPlaceholderLicenseKey($licenseKey)
            ? (string) config('libcontrol.discovery.secret')
            : $licenseKey;

        if ($signingKey === '') {
            return false;
        }

        $settings = \App\Models\PlatformSetting::current();
        $payload = [
            'uuid' => $ticket->uuid,
            'domain' => $this->currentDomain(),
            'app_url' => $this->syncAppUrl(),
            'fingerprint' => $this->syncCoordinator->fingerprint(),
            'library_code' => $settings->library_code,
            'library_name' => $settings->displayName(),
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'reporter_name' => $ticket->reporter_name,
            'reporter_email' => $ticket->reporter_email,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $token = hash_hmac('sha256', $body, $signingKey);
        $headers = [
            'X-Sync-Token' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if (! LicensedDeployment::isPlaceholderLicenseKey($licenseKey)) {
            $headers['X-License-Key'] = $licenseKey;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($this->supportEndpoint());

            if (! $response->successful()) {
                return false;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return false;
            }

            $ticket->update([
                'remote_id' => $data['id'] ?? $ticket->remote_id,
                'synced_at' => now(),
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function supportEndpoint(): string
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
