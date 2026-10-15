<?php

namespace App\Services\Payments;

use App\Models\GrowthOrder;
use App\Models\LicensedDeployment;
use App\Models\Payment;
use App\Services\Developer\DeploymentCommandService;
use App\Services\Growth\GrowthOrderService;
use App\Support\GrowthCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Hub-side (libcontrol.in) payments. Amounts are always priced here from hub config or the
 * hub's own quote — never from what a client install sends.
 */
class PaymentService
{
    public function __construct(
        private PhonePeClient $phonePe,
        private DeploymentCommandService $commands,
    ) {}

    /**
     * @param  array{
     *     purpose: string,
     *     item_key?: string|null,
     *     billing_cycle?: string|null,
     *     growth_order_uuid?: string|null,
     *     payer_name?: string|null,
     *     payer_email?: string|null,
     *     payer_phone?: string|null,
     *     return_url?: string|null,
     *     domain?: string|null,
     *     library_code?: string|null,
     *     library_name?: string|null,
     * }  $input
     * @return array{payment: Payment, redirect_url: string}
     */
    public function startCheckout(LicensedDeployment $deployment, array $input): array
    {
        if (! $this->phonePe->configured()) {
            throw ValidationException::withMessages(['payment' => 'Online payments are not available right now. Please contact Katsindia.']);
        }

        $priced = $this->price($deployment, $input);

        $payment = Payment::query()->create([
            'gateway' => 'phonepe',
            'purpose' => $priced['purpose'],
            'item_key' => $priced['item_key'],
            'item_name' => $priced['item_name'],
            'billing_cycle' => $priced['billing_cycle'],
            'amount_paise' => $priced['amount_paise'],
            'status' => Payment::STATUS_CREATED,
            'licensed_deployment_id' => $deployment->id,
            'deployment_domain' => LicensedDeployment::normalizeDomain((string) ($input['domain'] ?? '')) ?: null,
            'library_code' => $this->clip($input['library_code'] ?? null, 64),
            'library_name' => $this->clip($input['library_name'] ?? null, 255) ?? $deployment->client_name,
            'growth_order_uuid' => $priced['growth_order_uuid'],
            'payer_name' => $this->clip($input['payer_name'] ?? null, 255),
            'payer_email' => filter_var($input['payer_email'] ?? null, FILTER_VALIDATE_EMAIL) ?: null,
            'payer_phone' => $this->clip(preg_replace('/[^0-9+]/', '', (string) ($input['payer_phone'] ?? '')), 20),
            'return_url' => $this->safeReturnUrl($deployment, $input['return_url'] ?? null),
        ]);

        try {
            $order = $this->phonePe->createOrder(
                $payment->merchant_order_id,
                $payment->amount_paise,
                route('payments.return', $payment->uuid),
                'LibControl: '.$payment->item_name,
                [
                    'udf1' => $payment->purpose,
                    'udf2' => $payment->item_key,
                    'udf3' => (string) $deployment->id,
                    'udf4' => (string) $payment->library_name,
                ],
            );
        } catch (\Throwable $e) {
            $payment->update(['status' => Payment::STATUS_FAILED, 'error_code' => 'CREATE_FAILED']);

            throw ValidationException::withMessages(['payment' => $e->getMessage()]);
        }

        $phonePeRedirect = (string) $order['redirect_url'];

        $payment->update([
            'status' => Payment::STATUS_PENDING,
            'gateway_order_id' => $order['order_id'] ?: null,
            'meta' => array_merge($payment->meta ?? [], [
                'phonepe_redirect_url' => $phonePeRedirect,
            ]),
        ]);

        return [
            'payment' => $payment->fresh(),
            'redirect_url' => $this->signedLaunchUrl($payment->fresh()),
        ];
    }

    public function signedLaunchUrl(Payment $payment): string
    {
        $minutes = max(5, (int) config('payments.phonepe.launch_url_ttl_minutes', 30));
        $origin = (string) config('payments.phonepe.checkout_origin', config('app.url'));

        URL::forceRootUrl(rtrim($origin, '/'));

        return URL::temporarySignedRoute(
            'payments.launch',
            now()->addMinutes($minutes),
            ['uuid' => $payment->uuid],
            absolute: true,
        );
    }

    /**
     * Asks PhonePe for the authoritative state and fulfils exactly once.
     */
    public function refreshStatus(Payment $payment): Payment
    {
        if ($payment->isFinal() && ($payment->status !== Payment::STATUS_COMPLETED || $payment->fulfilled_at)) {
            return $payment;
        }

        $remote = $this->phonePe->orderStatus($payment->merchant_order_id);

        return DB::transaction(function () use ($payment, $remote) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $locked->last_checked_at = now();

            if ($remote === null) {
                if ($locked->created_at?->lt(now()->subHours(2))) {
                    $locked->status = Payment::STATUS_FAILED;
                    $locked->error_code = 'NOT_FOUND';
                }
                $locked->save();

                return $locked;
            }

            $state = strtoupper((string) ($remote['state'] ?? ''));
            $attempt = $this->latestAttempt($remote);

            if ($attempt) {
                $locked->gateway_transaction_id = $attempt['transactionId'] ?? $locked->gateway_transaction_id;
                $locked->payment_mode = isset($attempt['paymentMode']) ? (string) $attempt['paymentMode'] : $locked->payment_mode;
            }

            if ($state === 'COMPLETED') {
                if ((int) ($remote['amount'] ?? 0) !== $locked->amount_paise) {
                    Log::critical('PhonePe amount mismatch — payment not fulfilled.', [
                        'payment' => $locked->uuid,
                        'expected' => $locked->amount_paise,
                        'received' => $remote['amount'] ?? null,
                    ]);
                    $locked->status = Payment::STATUS_FAILED;
                    $locked->error_code = 'AMOUNT_MISMATCH';
                    $locked->save();

                    return $locked;
                }

                $locked->status = Payment::STATUS_COMPLETED;
                $locked->paid_at ??= now();
                $locked->error_code = null;
                $locked->save();

                if (! $locked->fulfilled_at) {
                    $this->fulfil($locked);
                    $locked->fulfilled_at = now();
                    $locked->save();
                    $this->notifyTeam($locked);
                }

                return $locked;
            }

            if ($state === 'FAILED') {
                $locked->status = Payment::STATUS_FAILED;
                $locked->error_code = $attempt['errorCode'] ?? ($remote['errorCode'] ?? 'FAILED');
            } else {
                $locked->status = Payment::STATUS_PENDING;
            }

            $locked->save();

            return $locked;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function summaryFor(LicensedDeployment $deployment): array
    {
        $this->refreshStalePending($deployment);
        $deployment->refresh();
        $tier = $deployment->planTier();

        return [
            'plan' => [
                'plan_tier' => $tier,
                'plan_label' => config("libcontrol.plans.{$tier}.label", ucfirst($tier)),
                'plan_paid_until' => $deployment->plan_paid_until?->toIso8601String(),
                'max_seats_override' => $deployment->max_seats_override,
                'max_halls_override' => $deployment->max_halls_override,
                'max_branches_override' => $deployment->max_branches_override,
            ],
            'prices' => config('payments.software_plans', []),
            'payments_enabled' => $this->phonePe->configured(),
            'payments' => Payment::query()
                ->where('licensed_deployment_id', $deployment->id)
                ->orderByDesc('id')
                ->limit(25)
                ->get()
                ->map(fn (Payment $p) => $this->publicPayload($p))
                ->values()
                ->all(),
        ];
    }

    /**
     * Abandoned checkouts stay PENDING at PhonePe until the order expires, and a customer may pay
     * then close the tab before the return page. Re-check a few when the library looks at billing.
     */
    private function refreshStalePending(LicensedDeployment $deployment): void
    {
        $stale = Payment::query()
            ->where('licensed_deployment_id', $deployment->id)
            ->whereIn('status', [Payment::STATUS_CREATED, Payment::STATUS_PENDING])
            ->where('created_at', '<=', now()->subSeconds(30))
            ->where(fn ($q) => $q->whereNull('last_checked_at')->orWhere('last_checked_at', '<=', now()->subMinute()))
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        foreach ($stale as $payment) {
            try {
                $this->refreshStatus($payment);
            } catch (\Throwable $e) {
                Log::info('Pending payment re-check failed.', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function publicPayload(Payment $payment): array
    {
        return [
            'uuid' => $payment->uuid,
            'purpose' => $payment->purpose,
            'purpose_label' => $payment->purposeLabel(),
            'item_key' => $payment->item_key,
            'item_name' => $payment->item_name,
            'billing_cycle' => $payment->billing_cycle,
            'amount_paise' => $payment->amount_paise,
            'amount_label' => $payment->amountLabel(),
            'status' => $payment->status,
            'status_label' => $payment->statusLabel(),
            'reference' => $payment->merchant_order_id,
            'growth_order_uuid' => $payment->growth_order_uuid,
            'created_at' => $payment->created_at?->toIso8601String(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{purpose: string, item_key: string, item_name: string, billing_cycle: ?string, amount_paise: int, growth_order_uuid: ?string}
     */
    private function price(LicensedDeployment $deployment, array $input): array
    {
        $purpose = (string) ($input['purpose'] ?? '');

        if ($purpose === Payment::PURPOSE_SOFTWARE_PLAN) {
            $key = (string) ($input['item_key'] ?? '');
            $cycle = (string) ($input['billing_cycle'] ?? '');
            $plan = config("payments.software_plans.{$key}");

            if (! is_array($plan) || ! in_array($cycle, ['monthly', 'yearly'], true) || (int) ($plan[$cycle] ?? 0) < 100) {
                throw ValidationException::withMessages(['payment' => 'Choose a valid LibControl plan.']);
            }

            $this->assertPlanChangeAllowed($deployment, $key);

            return [
                'purpose' => $purpose,
                'item_key' => $key,
                'item_name' => 'LibControl '.$plan['label'].' ('.($cycle === 'yearly' ? 'Yearly' : 'Monthly').')',
                'billing_cycle' => $cycle,
                'amount_paise' => (int) $plan[$cycle],
                'growth_order_uuid' => null,
            ];
        }

        if (in_array($purpose, [Payment::PURPOSE_GROWTH_PACKAGE, Payment::PURPOSE_GROWTH_SERVICE], true)) {
            $uuid = (string) ($input['growth_order_uuid'] ?? '');
            $order = $uuid !== ''
                ? GrowthOrder::query()
                    ->where('uuid', $uuid)
                    ->where('deployment_license_key_hash', $deployment->license_key_hash)
                    ->first()
                : null;

            if (! $order) {
                throw ValidationException::withMessages(['payment' => 'This request has not reached Katsindia yet. Please try again in a minute or contact support.']);
            }

            $expectedType = $purpose === Payment::PURPOSE_GROWTH_PACKAGE ? 'package' : 'service';
            if ($order->order_type !== $expectedType) {
                throw ValidationException::withMessages(['payment' => 'This request cannot be paid here.']);
            }

            if ($order->payment_status === 'paid') {
                throw ValidationException::withMessages(['payment' => 'This request is already paid.']);
            }

            if (in_array($order->status, [GrowthOrder::STATUS_CANCELLED, GrowthOrder::STATUS_COMPLETED], true)) {
                throw ValidationException::withMessages(['payment' => 'This request is closed.']);
            }

            if ($expectedType === 'package') {
                $package = GrowthCatalog::package($order->item_key);
                if (! is_array($package)) {
                    throw ValidationException::withMessages(['payment' => 'Unknown growth package.']);
                }

                if (! empty($package['demo_only'])) {
                    $checkoutDomain = LicensedDeployment::normalizeDomain(
                        (string) ($input['domain'] ?? $order->deployment_domain ?? '')
                    );
                    if (! GrowthCatalog::paymentTestCheckoutAllowed($checkoutDomain)) {
                        throw ValidationException::withMessages([
                            'payment' => 'The payment test package is only available on demo.libcontrol.in.',
                        ]);
                    }
                }

                $price = (int) ($package['price'] ?? 0);
                if ($price <= 0) {
                    throw ValidationException::withMessages(['payment' => 'Unknown growth package.']);
                }
                $amount = $price * 100;
                if ((int) $order->amount_paise !== $amount) {
                    $order->forceFill(['amount_paise' => $amount])->save();
                }
            } else {
                $amount = (int) $order->amount_paise;
                if ($amount < 100 || $order->status === GrowthOrder::STATUS_NEW) {
                    throw ValidationException::withMessages(['payment' => 'Katsindia will share a quote first. You can pay once the quote is ready.']);
                }
            }

            return [
                'purpose' => $purpose,
                'item_key' => $order->item_key,
                'item_name' => $order->item_name,
                'billing_cycle' => null,
                'amount_paise' => $amount,
                'growth_order_uuid' => $order->uuid,
            ];
        }

        throw ValidationException::withMessages(['payment' => 'Unknown payment type.']);
    }

    private function assertPlanChangeAllowed(LicensedDeployment $deployment, string $newTier): void
    {
        $current = $deployment->planTier();
        $ranks = (array) config('payments.plan_rank', []);

        if ($current === 'custom') {
            throw ValidationException::withMessages(['payment' => 'Your library is on a custom plan. Contact Katsindia to renew.']);
        }

        $paidUntil = $deployment->plan_paid_until;
        $downgrade = ($ranks[$newTier] ?? 0) < ($ranks[$current] ?? 0);

        if ($downgrade && $paidUntil && $paidUntil->isFuture()) {
            throw ValidationException::withMessages([
                'payment' => 'Your '.ucfirst($current).' plan is paid until '.$paidUntil->format('d M Y').'. You can switch to '.ucfirst($newTier).' after that date.',
            ]);
        }
    }

    private function fulfil(Payment $payment): void
    {
        match ($payment->purpose) {
            Payment::PURPOSE_SOFTWARE_PLAN => $this->fulfilPlan($payment),
            Payment::PURPOSE_GROWTH_PACKAGE, Payment::PURPOSE_GROWTH_SERVICE => $this->fulfilGrowth($payment),
            default => null,
        };
    }

    private function fulfilPlan(Payment $payment): void
    {
        /** @var LicensedDeployment|null $deployment */
        $deployment = LicensedDeployment::query()->whereKey($payment->licensed_deployment_id)->lockForUpdate()->first();

        if (! $deployment) {
            Log::error('Paid plan has no deployment.', ['payment' => $payment->uuid]);

            return;
        }

        $tier = $payment->item_key;
        $start = ($deployment->planTier() === $tier && $deployment->plan_paid_until?->isFuture())
            ? Carbon::parse($deployment->plan_paid_until)
            : now();
        $paidUntil = $payment->billing_cycle === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth();

        $defaults = (array) config("libcontrol.plans.{$tier}", []);
        $keepOverride = static function (?int $override, string $key) use ($defaults): ?int {
            $default = $defaults[$key] ?? null;

            return ($override !== null && $default !== null && $override > $default) ? $override : null;
        };

        $deployment->forceFill([
            'plan_tier' => $tier,
            'plan_paid_until' => $paidUntil,
            'max_seats_override' => $keepOverride($deployment->max_seats_override, 'max_seats'),
            'max_halls_override' => $keepOverride($deployment->max_halls_override, 'max_halls'),
            'max_branches_override' => $keepOverride($deployment->max_branches_override, 'max_branches'),
        ])->save();

        $this->commands->queue($deployment, 'set_plan', [
            'plan_tier' => $tier,
            'max_seats_override' => $deployment->max_seats_override,
            'max_halls_override' => $deployment->max_halls_override,
            'max_branches_override' => $deployment->max_branches_override,
            'plan_paid_until' => $paidUntil->toIso8601String(),
        ]);
    }

    private function fulfilGrowth(Payment $payment): void
    {
        $order = GrowthOrder::query()->where('uuid', $payment->growth_order_uuid)->lockForUpdate()->first();

        if (! $order) {
            Log::error('Paid growth order not found.', ['payment' => $payment->uuid]);

            return;
        }

        $order->payment_status = 'paid';
        $order->amount_paise = $payment->amount_paise;

        if (in_array($order->status, [GrowthOrder::STATUS_NEW, GrowthOrder::STATUS_QUOTED, GrowthOrder::STATUS_PAUSED], true)) {
            $order->status = GrowthOrder::STATUS_ACTIVE;
        }
        $order->activated_at ??= now();

        $note = 'Payment of '.$payment->amountLabel().' received via PhonePe (ref '.$payment->merchant_order_id.'). Our team will start shortly.';
        $order->admin_notes = trim(($order->admin_notes ? $order->admin_notes."\n\n" : '').$note);
        $order->save();

        app(GrowthOrderService::class)->applyStatusToProfile($order);

        if ($ticket = $order->supportTicket) {
            $ticket->forceFill([
                'status' => $order->ticketStatus(),
                'admin_notes' => $order->admin_notes,
            ])->save();
        }
    }

    private function notifyTeam(Payment $payment): void
    {
        $recipients = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('growth.notify_email')),
        ), fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));

        if ($recipients === []) {
            return;
        }

        $lines = [
            'Payment received on LibControl',
            '',
            'Item: '.$payment->item_name.' ('.$payment->purposeLabel().')',
            'Amount: '.$payment->amountLabel(),
            'Library: '.($payment->library_name ?: '—').' · '.($payment->deployment_domain ?: '—'),
            'Payer: '.($payment->payer_name ?: '—').' <'.($payment->payer_email ?: '—').'> '.($payment->payer_phone ?: ''),
            'Reference: '.$payment->merchant_order_id,
            'PhonePe transaction: '.($payment->gateway_transaction_id ?: '—'),
        ];

        $subject = 'Payment received: '.$payment->item_name.' — '.$payment->amountLabel();

        dispatch(static function () use ($recipients, $lines, $subject): void {
            try {
                Mail::raw(implode("\n", $lines), fn ($m) => $m->to($recipients)->subject($subject));
            } catch (\Throwable $e) {
                Log::warning('Payment notification email failed.', ['error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    /**
     * @param  array<string, mixed>  $remote
     * @return array<string, mixed>|null
     */
    private function latestAttempt(array $remote): ?array
    {
        $details = is_array($remote['paymentDetails'] ?? null) ? $remote['paymentDetails'] : [];
        $last = end($details);

        return is_array($last) ? $last : null;
    }

    private function safeReturnUrl(LicensedDeployment $deployment, mixed $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 500 || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (! in_array($scheme, ['https', 'http'], true) || $host === '') {
            return null;
        }

        if ($scheme === 'http' && app()->environment('production') && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        return $deployment->allowsDomain($host) ? $url : null;
    }

    private function clip(mixed $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
