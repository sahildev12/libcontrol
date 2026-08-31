<?php

namespace App\Support\Runtime;

use App\Models\LicensedDeployment;
use Illuminate\Support\Facades\Http;

class SyncCoordinator
{
    public function __construct(
        private DeploymentState $state,
    ) {}

    public function fingerprint(): string
    {
        $parts = [
            (string) config('app.key'),
            (string) config('database.connections.'.config('database.default').'.database'),
            (string) config('app.url'),
        ];

        return hash('sha256', implode('|', $parts));
    }

    public function maybeSync(): void
    {
        if (config('libspace.license_server.enabled')) {
            return;
        }

        $licenseKey = (string) config('libspace.deployment.license_key');

        if ($licenseKey === '') {
            return;
        }

        if (! $this->state->shouldSync()) {
            return;
        }

        $this->sync();
    }

    public function sync(): void
    {
        if (config('libspace.license_server.enabled')) {
            return;
        }

        $licenseKey = (string) config('libspace.deployment.license_key');

        if ($licenseKey === '') {
            return;
        }

        $payload = [
            'domain' => $this->currentDomain(),
            'app_url' => (string) config('app.url'),
            'fingerprint' => $this->fingerprint(),
            'meta' => [
                'php' => PHP_VERSION,
                'app' => (string) config('app.version', '1.0'),
            ],
        ];

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $token = hash_hmac('sha256', $body, $licenseKey);
        $endpoint = $this->endpoint();

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'X-Sync-Token' => $token,
                    'X-License-Key' => $licenseKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint);

            if (! $response->successful()) {
                return;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return;
            }

            $this->state->store([
                'status' => (string) ($data['status'] ?? 'pending'),
                'authorized' => ($data['status'] ?? '') === 'ok',
                'grace_until' => $data['grace_until'] ?? null,
            ]);
        } catch (\Throwable) {
            // Fail open on network errors.
        }
    }

    private function currentDomain(): string
    {
        if (app()->runningInConsole()) {
            return LicensedDeployment::normalizeDomain((string) config('app.url'));
        }

        $host = request()->getHost();

        if ($host !== '') {
            return LicensedDeployment::normalizeDomain($host);
        }

        return LicensedDeployment::normalizeDomain((string) config('app.url'));
    }

    private function endpoint(): string
    {
        $override = config('libspace.deployment.sync_endpoint');

        if (is_string($override) && $override !== '') {
            return $override;
        }

        $encoded = (string) config('libspace.deployment.sync_endpoint_encoded');

        if ($encoded !== '') {
            $decoded = base64_decode($encoded, true);

            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return rtrim((string) config('app.url'), '/').'/api/runtime/sync';
    }
}
