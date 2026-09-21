<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstallationEvent;
use App\Models\LibraryRegistry;
use App\Models\LicensedDeployment;
use App\Services\Developer\DeploymentCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RuntimeSyncController extends Controller
{
    public function __construct(
        private DeploymentCommandService $deploymentCommands,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json([
                'status' => 'pending',
                'grace_until' => now()->addDays((int) config('libcontrol.deployment.grace_days', 7))->toIso8601String(),
            ]);
        }

        $licenseKey = trim((string) $request->header('X-License-Key', ''));
        $token = (string) $request->header('X-Sync-Token', '');

        if ($token === '') {
            return $this->unauthorizedResponse();
        }

        $domain = LicensedDeployment::normalizeDomain((string) ($payload['domain'] ?? ''));
        $fingerprint = (string) ($payload['fingerprint'] ?? '');

        if ($domain === '' || $fingerprint === '') {
            return $this->unauthorizedResponse();
        }

        LibraryRegistry::upsertFromSyncPayload($payload);

        $eventPayload = [
            'domain' => $domain,
            'app_url' => $payload['app_url'] ?? null,
            'fingerprint' => $fingerprint,
            'server_ip' => $request->ip(),
            'php_version' => is_array($payload['meta'] ?? null) ? ($payload['meta']['php'] ?? null) : null,
            'app_version' => is_array($payload['meta'] ?? null) ? ($payload['meta']['app'] ?? null) : null,
        ];

        if (LicensedDeployment::isPlaceholderLicenseKey($licenseKey)) {
            return $this->recordDiscoveryPing($rawBody, $token, $eventPayload);
        }

        $expected = hash_hmac('sha256', $rawBody, $licenseKey);

        if (! hash_equals($expected, $token)) {
            return $this->unauthorizedResponse();
        }

        $deployment = LicensedDeployment::findByLicenseKey($licenseKey);
        $licenseKeyHash = LicensedDeployment::hashKey($licenseKey);
        $graceDays = (int) config('libcontrol.deployment.grace_days', 7);

        if (! $deployment || ! $deployment->active) {
            InstallationEvent::recordHeartbeat($licenseKeyHash, $eventPayload, false);

            return $this->pendingResponse($graceDays, $this->licenseKeyCommandsForDomain($domain));
        }

        $graceDays = $deployment->grace_days ?: $graceDays;
        $authorized = $deployment->allowsDomain($domain);

        InstallationEvent::recordHeartbeat($licenseKeyHash, $eventPayload, $authorized);

        if ($authorized) {
            $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

            if (isset($meta['command_results']) && is_array($meta['command_results'])) {
                $this->deploymentCommands->recordResults($meta['command_results']);
            }

            return response()->json([
                'status' => 'ok',
                'commands' => $this->deploymentCommands->pendingForDeployment($deployment),
            ]);
        }

        return $this->pendingResponse($graceDays, $this->licenseKeyCommandsForDomain($domain));
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    private function recordDiscoveryPing(string $rawBody, string $token, array $eventPayload): JsonResponse
    {
        $secret = (string) config('libcontrol.discovery.secret');
        $expected = hash_hmac('sha256', $rawBody, $secret);

        if ($secret === '' || ! hash_equals($expected, $token)) {
            return $this->unauthorizedResponse();
        }

        InstallationEvent::recordHeartbeat(
            LicensedDeployment::discoveryKeyHash(),
            $eventPayload,
            false,
        );

        $graceDays = (int) config('libcontrol.deployment.grace_days', 7);
        $domain = LicensedDeployment::normalizeDomain((string) ($eventPayload['domain'] ?? ''));

        return $this->pendingResponse($graceDays, $this->licenseKeyCommandsForDomain($domain));
    }

    /**
     * @param  list<array{id: string, action: string, payload: array<string, mixed>|null}>  $commands
     */
    private function pendingResponse(int $graceDays, array $commands = []): JsonResponse
    {
        $payload = [
            'status' => 'pending',
            'grace_until' => now()->addDays($graceDays)->toIso8601String(),
        ];

        if ($commands !== []) {
            $payload['commands'] = $commands;
        }

        return response()->json($payload);
    }

    /**
     * @return list<array{id: string, action: string, payload: array<string, mixed>|null}>
     */
    private function licenseKeyCommandsForDomain(string $domain): array
    {
        $deployment = LicensedDeployment::findActiveByDomain($domain);

        if (! $deployment) {
            return [];
        }

        return $this->deploymentCommands->pendingLicenseKeyUpdatesForDeployment($deployment);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        $graceDays = (int) config('libcontrol.deployment.grace_days', 7);

        return response()->json([
            'status' => 'pending',
            'grace_until' => Carbon::now()->addDays($graceDays)->toIso8601String(),
        ]);
    }
}
