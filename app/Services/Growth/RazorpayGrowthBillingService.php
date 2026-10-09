<?php

namespace App\Services\Growth;

use App\Models\GrowthOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RazorpayGrowthBillingService
{
    public function enabled(): bool
    {
        return (bool) config('growth.razorpay.enabled')
            && filled(config('growth.razorpay.key_id'))
            && filled(config('growth.razorpay.key_secret'));
    }

    /**
     * Prefer hosted payment page URL from config; otherwise create a Payment Link via API.
     *
     * @return array{url: string|null, payment_link_id: string|null, error: string|null}
     */
    public function checkoutUrlForOrder(GrowthOrder $order): array
    {
        if (! $this->enabled()) {
            return ['url' => null, 'payment_link_id' => null, 'error' => 'Razorpay is not configured.'];
        }

        if ($order->order_type === 'package') {
            $pageUrl = config("growth.razorpay.payment_page_urls.{$order->item_key}");
            if (is_string($pageUrl) && $pageUrl !== '') {
                return ['url' => $pageUrl, 'payment_link_id' => null, 'error' => null];
            }
        }

        $amount = $order->amount_paise;
        if (! $amount || $amount < 100) {
            return ['url' => null, 'payment_link_id' => null, 'error' => 'Order amount is not set for checkout.'];
        }

        try {
            $response = Http::withBasicAuth(
                (string) config('growth.razorpay.key_id'),
                (string) config('growth.razorpay.key_secret'),
            )->post('https://api.razorpay.com/v1/payment_links', [
                'amount' => $amount,
                'currency' => config('growth.razorpay.currency', 'INR'),
                'accept_partial' => false,
                'description' => 'LibControl Growth: '.$order->item_name,
                'customer' => array_filter([
                    'name' => $order->contact_name,
                    'email' => $order->contact_email,
                    'contact' => $order->contact_phone,
                ]),
                'notify' => ['sms' => false, 'email' => true],
                'reminder_enable' => true,
                'notes' => [
                    'growth_order_uuid' => $order->uuid,
                    'item_key' => $order->item_key,
                    'order_type' => $order->order_type,
                ],
                'callback_url' => url('/growth?paid=1'),
                'callback_method' => 'get',
            ]);

            if (! $response->successful()) {
                Log::warning('Razorpay payment link failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['url' => null, 'payment_link_id' => null, 'error' => 'Could not create Razorpay payment link.'];
            }

            $data = $response->json();
            $linkId = is_array($data) ? ($data['id'] ?? null) : null;
            $url = is_array($data) ? ($data['short_url'] ?? null) : null;

            if ($linkId) {
                $order->forceFill([
                    'razorpay_payment_link_id' => $linkId,
                    'payment_status' => 'pending',
                ])->save();
            }

            return [
                'url' => is_string($url) ? $url : null,
                'payment_link_id' => is_string($linkId) ? $linkId : null,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Razorpay payment link exception.', ['message' => $e->getMessage()]);

            return ['url' => null, 'payment_link_id' => null, 'error' => $e->getMessage()];
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = (string) config('growth.razorpay.webhook_secret', '');
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void
    {
        $event = (string) ($payload['event'] ?? '');
        $entity = $payload['payload']['payment_link']['entity']
            ?? $payload['payload']['payment']['entity']
            ?? $payload['payload']['subscription']['entity']
            ?? null;

        if (! is_array($entity)) {
            return;
        }

        $uuid = $entity['notes']['growth_order_uuid'] ?? null;
        if (! is_string($uuid) || $uuid === '') {
            return;
        }

        $order = GrowthOrder::query()->where('uuid', $uuid)->first();
        if (! $order) {
            return;
        }

        if (str_contains($event, 'paid') || str_contains($event, 'captured') || $event === 'payment_link.paid') {
            $order->forceFill([
                'payment_status' => 'paid',
                'razorpay_payment_id' => $entity['payment_id'] ?? $entity['id'] ?? $order->razorpay_payment_id,
                'status' => $order->status === GrowthOrder::STATUS_NEW ? GrowthOrder::STATUS_QUOTED : $order->status,
            ])->save();
        }

        if (str_contains($event, 'failed')) {
            $order->forceFill(['payment_status' => 'failed'])->save();
        }
    }
}
