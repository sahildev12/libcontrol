<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PhonePeClient
{
    public function configured(): bool
    {
        return filled(config('payments.phonepe.client_id'))
            && filled(config('payments.phonepe.client_secret'))
            && array_key_exists($this->environment(), (array) config('payments.phonepe.endpoints', []));
    }

    public function environment(): string
    {
        return (string) config('payments.phonepe.env', 'sandbox') === 'production' ? 'production' : 'sandbox';
    }

    /**
     * @param  array<string, string>  $metaInfo
     * @return array{order_id: string, state: string, redirect_url: string, expire_at: int|null}
     */
    public function createOrder(string $merchantOrderId, int $amountPaise, string $redirectUrl, string $message, array $metaInfo = []): array
    {
        $payload = [
            'merchantOrderId' => $merchantOrderId,
            'amount' => $amountPaise,
            'expireAfter' => max(300, min(3600, (int) config('payments.phonepe.order_expire_seconds', 1200))),
            'paymentFlow' => [
                'type' => 'PG_CHECKOUT',
                'message' => mb_substr($message, 0, 100),
                'merchantUrls' => [
                    'redirectUrl' => $redirectUrl,
                ],
            ],
        ];

        $metaInfo = $this->sanitizeMetaInfo($metaInfo);
        if ($metaInfo !== []) {
            $payload['metaInfo'] = $metaInfo;
        }

        $response = $this->authorizedRequest('post', '/checkout/v2/pay', $payload);
        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || blank($data['redirectUrl'] ?? null)) {
            Log::warning('PhonePe create order failed.', [
                'merchant_order_id' => $merchantOrderId,
                'status' => $response->status(),
                'code' => is_array($data) ? ($data['code'] ?? null) : null,
                'message' => is_array($data) ? ($data['message'] ?? null) : null,
            ]);

            throw new RuntimeException('PhonePe could not start this payment. Please try again in a minute.');
        }

        return [
            'order_id' => (string) ($data['orderId'] ?? ''),
            'state' => (string) ($data['state'] ?? 'PENDING'),
            'redirect_url' => (string) $data['redirectUrl'],
            'expire_at' => isset($data['expireAt']) ? (int) $data['expireAt'] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null  Null when PhonePe does not know the order.
     */
    public function orderStatus(string $merchantOrderId): ?array
    {
        $response = $this->authorizedRequest(
            'get',
            '/checkout/v2/order/'.rawurlencode($merchantOrderId).'/status?details=false',
        );
        $data = $response->json();

        if ($response->successful() && is_array($data) && isset($data['state'])) {
            return $data;
        }

        if ($response->status() === 204 || $response->status() === 404
            || (is_array($data) && ($data['code'] ?? null) === 'INVALID_MERCHANT_ORDER_ID')) {
            return null;
        }

        Log::warning('PhonePe order status failed.', [
            'merchant_order_id' => $merchantOrderId,
            'status' => $response->status(),
            'code' => is_array($data) ? ($data['code'] ?? null) : null,
        ]);

        throw new RuntimeException('Could not confirm the payment status with PhonePe.');
    }

    public function webhookAuthorizationIsValid(?string $header): bool
    {
        $username = (string) config('payments.phonepe.webhook_username', '');
        $password = (string) config('payments.phonepe.webhook_password', '');

        if ($username === '' || $password === '' || blank($header)) {
            return false;
        }

        $expected = hash('sha256', $username.':'.$password);
        $received = strtolower(trim((string) preg_replace('/^(SHA256|Bearer|O-Bearer)\s+/i', '', trim((string) $header))));

        return hash_equals($expected, $received);
    }

    public function forgetToken(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function authorizedRequest(string $method, string $path, array $payload = []): Response
    {
        $send = function (string $token) use ($method, $path, $payload): Response {
            $request = Http::timeout((int) config('payments.phonepe.timeout', 20))
                ->acceptJson()
                ->withHeaders(['Authorization' => 'O-Bearer '.$token]);

            $url = $this->baseUrl().$path;

            return $method === 'get' ? $request->get($url) : $request->asJson()->post($url, $payload);
        };

        $response = $send($this->accessToken());

        if ($response->status() === 401) {
            $this->forgetToken();
            $response = $send($this->accessToken());
        }

        return $response;
    }

    private function accessToken(): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('PhonePe is not configured on this server.');
        }

        $cached = Cache::get($this->tokenCacheKey());
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::timeout((int) config('payments.phonepe.timeout', 20))
            ->asForm()
            ->acceptJson()
            ->post($this->endpoint('auth'), [
                'client_id' => (string) config('payments.phonepe.client_id'),
                'client_version' => (string) config('payments.phonepe.client_version', '1'),
                'client_secret' => (string) config('payments.phonepe.client_secret'),
                'grant_type' => 'client_credentials',
            ]);

        $token = (string) ($response->json('access_token') ?? '');

        if (! $response->successful() || $token === '') {
            $body = $response->json();
            $code = is_array($body) ? (string) ($body['code'] ?? $body['errorCode'] ?? '') : '';
            $message = is_array($body) ? (string) ($body['message'] ?? '') : '';

            Log::error('PhonePe auth token request failed.', [
                'status' => $response->status(),
                'environment' => $this->environment(),
                'code' => $code !== '' ? $code : null,
                'message' => $message !== '' ? $message : null,
            ]);

            if ($code === 'CLIENT_NOT_FOUND' || str_contains($message, 'Client Not Found')) {
                throw new RuntimeException(
                    'PhonePe production credentials are missing or still set to UAT/test keys. '
                    .'In developer.phonepe.com (live mode), copy the production Client ID and Client Secret into the hub .env, then run php artisan config:cache.'
                );
            }

            throw new RuntimeException('Could not authenticate with PhonePe.');
        }

        $expiresAt = (int) ($response->json('expires_at') ?? 0);
        $ttl = $expiresAt > 0 ? $expiresAt - time() - 120 : 600;

        if ($ttl > 60) {
            Cache::put($this->tokenCacheKey(), $token, $ttl);
        }

        return $token;
    }

    /**
     * PhonePe allows udf1–udf10 up to 256 chars; udf11–udf15 are short alphanumeric fields.
     *
     * @param  array<string, string>  $metaInfo
     * @return array<string, string>
     */
    private function sanitizeMetaInfo(array $metaInfo): array
    {
        $clean = [];

        foreach ($metaInfo as $key => $value) {
            if (! preg_match('/^udf([1-9]|10)$/', (string) $key)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                $clean[$key] = mb_substr($value, 0, 256);
            }
        }

        return $clean;
    }

    private function baseUrl(): string
    {
        return rtrim($this->endpoint('base'), '/');
    }

    private function endpoint(string $name): string
    {
        return (string) config('payments.phonepe.endpoints.'.$this->environment().'.'.$name);
    }

    private function tokenCacheKey(): string
    {
        return 'phonepe:token:'.$this->environment().':'.sha1((string) config('payments.phonepe.client_id'));
    }
}
