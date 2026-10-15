<?php

namespace App\Services\Payments;

use App\Models\LicensedDeployment;
use App\Models\PlatformSetting;
use App\Services\SupportTicketSyncService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Client-install side: asks the Katsindia hub to start PhonePe payments. Gateway keys stay on the hub.
 */
class HubPaymentClient
{
    public function __construct(
        private SupportTicketSyncService $ticketSync,
    ) {}

    public function available(): bool
    {
        return ! config('libcontrol.license_server.enabled')
            && ! $this->blockedOnDemo()
            && ! LicensedDeployment::isPlaceholderLicenseKey((string) config('libcontrol.deployment.license_key'));
    }

    private function blockedOnDemo(): bool
    {
        return config('libcontrol.demo.enabled') && ! config('libcontrol.demo.payments_enabled');
    }

    public function unavailableReason(): ?string
    {
        if ($this->blockedOnDemo()) {
            return 'Online payments are turned off on the demo site.';
        }

        if (LicensedDeployment::isPlaceholderLicenseKey((string) config('libcontrol.deployment.license_key'))) {
            return 'Online payments need this installation\'s LibControl license key. Contact Katsindia.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, redirect_url: ?string, message: ?string, payment: array<string, mixed>|null}
     */
    public function checkout(array $data): array
    {
        if (! $this->available()) {
            return ['ok' => false, 'redirect_url' => null, 'message' => $this->unavailableReason() ?? 'Online payments are not available.', 'payment' => null];
        }

        $settings = PlatformSetting::current();
        $response = $this->post('/api/payments/checkout', $data + [
            'library_code' => $settings->library_code,
            'library_name' => $settings->displayName(),
        ]);

        if (! $response) {
            return ['ok' => false, 'redirect_url' => null, 'message' => 'Could not reach Katsindia to start the payment. Please try again.', 'payment' => null];
        }

        $json = $response->json();
        $redirect = is_array($json) ? (string) ($json['redirect_url'] ?? '') : '';

        if (! $response->successful() || ! $this->isAllowedCheckoutRedirect($redirect)) {
            return [
                'ok' => false,
                'redirect_url' => null,
                'message' => is_array($json) && filled($json['message'] ?? null) ? (string) $json['message'] : 'Payment could not be started (HTTP '.$response->status().').',
                'payment' => null,
            ];
        }

        return ['ok' => true, 'redirect_url' => $redirect, 'message' => null, 'payment' => $json['payment'] ?? null];
    }

    /**
     * Current plan + payment history from the hub. Also applies the paid plan locally so
     * limits update right after checkout instead of waiting for the next heartbeat.
     *
     * @return array<string, mixed>|null
     */
    public function summary(?string $refreshPaymentUuid = null): ?array
    {
        if (! $this->available()) {
            return null;
        }

        $payload = [];
        if ($refreshPaymentUuid && preg_match('/^[0-9a-f-]{36}$/i', $refreshPaymentUuid)) {
            $payload['refresh_payment'] = strtolower($refreshPaymentUuid);
        }

        $response = $this->post('/api/payments/summary', $payload);
        $json = $response?->json();

        if (! $response?->successful() || ! is_array($json)) {
            return null;
        }

        if (is_array($json['plan'] ?? null)) {
            $this->applyPlan($json['plan']);
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function applyPlan(array $plan): void
    {
        $tier = (string) ($plan['plan_tier'] ?? '');
        if (! array_key_exists($tier, (array) config('libcontrol.plans', [])) || blank($plan['plan_paid_until'] ?? null)) {
            return;
        }

        $settings = PlatformSetting::current();
        $attributes = [
            'plan_tier' => $tier,
            'max_seats_override' => is_numeric($plan['max_seats_override'] ?? null) ? (int) $plan['max_seats_override'] : null,
            'max_halls_override' => is_numeric($plan['max_halls_override'] ?? null) ? (int) $plan['max_halls_override'] : null,
            'max_branches_override' => is_numeric($plan['max_branches_override'] ?? null) ? (int) $plan['max_branches_override'] : null,
        ];

        if (Schema::hasColumn('platform_settings', 'plan_paid_until')) {
            $attributes['plan_paid_until'] = filled($plan['plan_paid_until'] ?? null) ? Carbon::parse((string) $plan['plan_paid_until']) : null;
        }

        $settings->fill($attributes);
        if ($settings->isDirty()) {
            $settings->save();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function post(string $path, array $data): ?\Illuminate\Http\Client\Response
    {
        $licenseKey = trim((string) config('libcontrol.deployment.license_key'));
        $endpoint = $this->hubBaseUrl().$path;
        $body = json_encode($data + ['domain' => $this->currentDomain()], JSON_THROW_ON_ERROR);

        try {
            return Http::timeout(20)
                ->withHeaders([
                    'X-Sync-Token' => hash_hmac('sha256', $body, $licenseKey),
                    'X-License-Key' => $licenseKey,
                    'Accept' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint);
        } catch (\Throwable $e) {
            Log::warning('Hub payment request failed.', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function hubBaseUrl(): string
    {
        $support = $this->ticketSync->supportEndpoint();

        return rtrim(str_replace('/api/support/tickets', '', $support), '/');
    }

    private function currentDomain(): string
    {
        $host = request()->getHost();

        return LicensedDeployment::normalizeDomain($host !== '' ? $host : (string) config('app.url'));
    }

    private function isAllowedCheckoutRedirect(string $url): bool
    {
        return $this->isPhonePeUrl($url) || $this->isHubLaunchUrl($url);
    }

    private function isPhonePeUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'phonepe.com' || str_ends_with($host, '.phonepe.com');
    }

    private function isHubLaunchUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }

        $hubHost = strtolower((string) parse_url($this->hubBaseUrl(), PHP_URL_HOST));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($hubHost === '' || $host !== $hubHost) {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return (bool) preg_match('#^/payments/[0-9a-f-]{36}/launch$#i', $path)
            && str_contains((string) parse_url($url, PHP_URL_QUERY), 'signature=');
    }
}
