<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\DeploymentCommand;
use App\Models\GrowthOrder;
use App\Models\LicensedDeployment;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PhonePePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private const LICENSE_KEY = 'ls_test_license_key_for_payments_1234567890';

    private string $remoteState = 'PENDING';

    private ?int $remoteAmount = null;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
        Config::set('payments.phonepe', array_merge(config('payments.phonepe'), [
            'env' => 'sandbox',
            'client_id' => 'TEST_CLIENT',
            'client_secret' => 'TEST_SECRET',
            'webhook_username' => 'hookuser',
            'webhook_password' => 'hookpass',
        ]));

        Http::fake(function (HttpRequest $request) {
            $url = $request->url();

            if (str_contains($url, '/oauth/token')) {
                return Http::response(['access_token' => 'tok', 'expires_at' => time() + 3600, 'token_type' => 'O-Bearer']);
            }

            if (str_contains($url, '/checkout/v2/pay')) {
                return Http::response([
                    'orderId' => 'OMO'.Str::random(8),
                    'state' => 'PENDING',
                    'expireAt' => (time() + 1200) * 1000,
                    'redirectUrl' => 'https://mercury-uat.phonepe.com/transact/uat_v2?token=abc',
                ]);
            }

            if (str_contains($url, '/checkout/v2/order/')) {
                preg_match('#/order/([^/]+)/status#', $url, $m);
                $payment = Payment::query()->where('merchant_order_id', urldecode($m[1] ?? ''))->first();

                return Http::response([
                    'orderId' => 'OMO123',
                    'state' => $this->remoteState,
                    'amount' => $this->remoteAmount ?? $payment?->amount_paise,
                    'paymentDetails' => [[
                        'transactionId' => 'OM-TXN-1',
                        'paymentMode' => 'UPI_QR',
                        'state' => $this->remoteState,
                    ]],
                ]);
            }

            return null;
        });
    }

    public function test_checkout_rejects_bad_signatures_unknown_keys_and_foreign_domains(): void
    {
        $this->deployment();
        $payload = ['purpose' => 'software_plan', 'item_key' => 'pro', 'billing_cycle' => 'monthly', 'domain' => 'client.test'];
        $body = json_encode($payload);

        $this->call('POST', '/api/payments/checkout', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_LICENSE_KEY' => self::LICENSE_KEY,
            'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, 'wrong-key'),
        ], $body)->assertStatus(401);

        $this->signedPost('/api/payments/checkout', $payload, 'ls_someone_elses_key_000000000000000000')->assertStatus(403);
        $this->signedPost('/api/payments/checkout', ['domain' => 'evil.test'] + $payload)->assertStatus(403);
        $this->signedPost('/api/payments/checkout', $payload, LicensedDeployment::PLACEHOLDER_LICENSE_KEY)->assertStatus(401);

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_plan_checkout_is_priced_by_the_hub_not_the_client(): void
    {
        $this->deployment();

        $response = $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan',
            'item_key' => 'pro',
            'billing_cycle' => 'monthly',
            'amount' => 1,
            'amount_paise' => 100,
            'domain' => 'client.test',
            'return_url' => 'https://evil.test/steal',
        ])->assertCreated();

        $launchUrl = (string) $response->json('redirect_url');
        $this->assertStringContainsString('/payments/', $launchUrl);
        $this->assertStringContainsString('/launch', $launchUrl);

        $payment = Payment::query()->sole();
        $this->assertStringStartsWith('https://mercury-uat.phonepe.com/', $payment->meta['phonepe_redirect_url'] ?? '');
        $this->get($launchUrl)
            ->assertOk()
            ->assertSee('libcontrol.in', false)
            ->assertSee('mercury-uat.phonepe.com', false);

        $this->assertSame(99900, $payment->amount_paise);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertNull($payment->return_url, 'Return URL outside the deployment domains must be dropped.');

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/checkout/v2/pay')
            && $r['amount'] === 99900
            && $r['merchantOrderId'] === $payment->merchant_order_id
            && $r->header('Authorization')[0] === 'O-Bearer tok'
            && str_contains($r['paymentFlow']['merchantUrls']['redirectUrl'], '/payments/'.$payment->uuid.'/return'));
    }

    public function test_webhook_rejects_wrong_credentials(): void
    {
        $this->postJson('/api/webhooks/phonepe', ['event' => 'checkout.order.completed', 'payload' => ['merchantOrderId' => 'x']], [
            'Authorization' => hash('sha256', 'hookuser:wrong'),
        ])->assertStatus(401);
    }

    public function test_completed_webhook_upgrades_the_plan_exactly_once(): void
    {
        $deployment = $this->deployment();
        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan', 'item_key' => 'pro', 'billing_cycle' => 'monthly', 'domain' => 'client.test',
        ])->assertCreated();
        $payment = Payment::query()->sole();

        $this->remoteState = 'COMPLETED';

        foreach (range(1, 2) as $_) {
            $this->postJson('/api/webhooks/phonepe', [
                'event' => 'checkout.order.completed',
                'payload' => ['merchantOrderId' => $payment->merchant_order_id, 'state' => 'COMPLETED', 'amount' => 99900],
            ], ['Authorization' => hash('sha256', 'hookuser:hookpass')])->assertOk();
        }

        $payment->refresh();
        $deployment->refresh();

        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertNotNull($payment->fulfilled_at);
        $this->assertSame('OM-TXN-1', $payment->gateway_transaction_id);
        $this->assertSame('pro', $deployment->plan_tier);
        $this->assertTrue($deployment->plan_paid_until->between(now()->addMonth()->subMinute(), now()->addMonth()->addMinute()));

        $commands = DeploymentCommand::query()->where('licensed_deployment_id', $deployment->id)->where('action', 'set_plan')->get();
        $this->assertCount(1, $commands);
        $this->assertSame('pro', $commands->first()->payload['plan_tier']);
        $this->assertNotEmpty($commands->first()->payload['plan_paid_until']);
    }

    public function test_renewing_the_same_plan_extends_the_paid_period(): void
    {
        $deployment = $this->deployment(['plan_tier' => 'starter', 'plan_paid_until' => now()->addDays(10)]);
        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan', 'item_key' => 'starter', 'billing_cycle' => 'yearly', 'domain' => 'client.test',
        ])->assertCreated();

        $this->remoteState = 'COMPLETED';
        $this->get(route('payments.return', Payment::query()->sole()->uuid))->assertRedirect(config('app.url'));

        $this->assertTrue($deployment->fresh()->plan_paid_until->greaterThan(now()->addYear()->addDays(9)));
    }

    public function test_amount_mismatch_is_never_fulfilled(): void
    {
        $deployment = $this->deployment();
        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan', 'item_key' => 'pro', 'billing_cycle' => 'monthly', 'domain' => 'client.test',
        ])->assertCreated();

        $this->remoteState = 'COMPLETED';
        $this->remoteAmount = 100;

        $this->get(route('payments.return', Payment::query()->sole()->uuid))->assertRedirect(config('app.url'));

        $this->assertSame('AMOUNT_MISMATCH', Payment::query()->sole()->error_code);
        $this->assertNotSame('pro', $deployment->fresh()->plan_tier);
        $this->assertSame(0, DeploymentCommand::query()->count());
    }

    public function test_downgrade_is_blocked_while_a_higher_plan_is_paid(): void
    {
        $this->deployment(['plan_tier' => 'pro', 'plan_paid_until' => now()->addMonth()]);

        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan', 'item_key' => 'starter', 'billing_cycle' => 'monthly', 'domain' => 'client.test',
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Your Pro plan is paid until '.now()->addMonth()->format('d M Y').'. You can switch to Starter after that date.']);
    }

    public function test_growth_service_can_only_be_paid_after_a_hub_quote(): void
    {
        $deployment = $this->deployment();
        $order = GrowthOrder::query()->create([
            'order_type' => 'service',
            'item_key' => 'local_seo',
            'item_name' => 'Local SEO',
            'status' => GrowthOrder::STATUS_NEW,
            'deployment_license_key_hash' => $deployment->license_key_hash,
            'amount_paise' => 100,
        ]);
        $payload = ['purpose' => 'growth_service', 'growth_order_uuid' => $order->uuid, 'domain' => 'client.test'];

        $this->signedPost('/api/payments/checkout', $payload)->assertStatus(422);

        $this->actingAs($this->admin(Admin::TYPE_DEVELOPER))
            ->patch(route('developer.growth-orders.update', $order), ['status' => 'quoted', 'quote_amount' => 6500])
            ->assertRedirect();
        $this->assertSame(650000, $order->fresh()->amount_paise);

        $this->signedPost('/api/payments/checkout', $payload)->assertCreated();
        $this->assertSame(650000, Payment::query()->sole()->amount_paise);

        $this->remoteState = 'COMPLETED';
        $this->get(route('payments.return', Payment::query()->sole()->uuid))->assertRedirect(config('app.url'));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(GrowthOrder::STATUS_ACTIVE, $order->status);

        $this->signedPost('/api/payments/checkout', $payload)->assertStatus(422);
    }

    public function test_growth_package_price_comes_from_the_hub_catalog_and_other_installs_cannot_pay_it(): void
    {
        $deployment = $this->deployment();
        $order = GrowthOrder::query()->create([
            'order_type' => 'package',
            'item_key' => 'grow',
            'item_name' => 'Grow',
            'status' => GrowthOrder::STATUS_NEW,
            'deployment_license_key_hash' => $deployment->license_key_hash,
            'amount_paise' => 100,
        ]);

        $otherKey = 'ls_other_install_key_00000000000000000000';
        LicensedDeployment::query()->create([
            'client_name' => 'Other',
            'license_key_hash' => LicensedDeployment::hashKey($otherKey),
            'allowed_domains' => ['other.test'],
            'active' => true,
        ]);
        $this->signedPost('/api/payments/checkout', ['purpose' => 'growth_package', 'growth_order_uuid' => $order->uuid, 'domain' => 'other.test'], $otherKey)
            ->assertStatus(422);

        $this->signedPost('/api/payments/checkout', ['purpose' => 'growth_package', 'growth_order_uuid' => $order->uuid, 'domain' => 'client.test'])
            ->assertCreated();

        $this->assertSame(799900, Payment::query()->sole()->amount_paise);
    }

    public function test_client_billing_page_starts_phonepe_checkout_through_the_hub(): void
    {
        Config::set('libcontrol.license_server.enabled', false);
        Config::set('libcontrol.deployment.license_key', self::LICENSE_KEY);
        Config::set('libcontrol.deployment.sync_endpoint', 'https://hub.test/api/runtime/sync');

        Http::fake([
            'hub.test/api/payments/summary' => Http::response([
                'plan' => ['plan_tier' => 'pro', 'plan_label' => 'Pro', 'plan_paid_until' => now()->addMonth()->toIso8601String()],
                'prices' => config('payments.software_plans'),
                'payments_enabled' => true,
                'payments' => [],
            ]),
            'hub.test/api/payments/checkout' => Http::response(['redirect_url' => 'https://mercury-uat.phonepe.com/transact/x', 'payment' => []], 201),
        ]);

        $admin = $this->admin(Admin::TYPE_CLIENT);

        $this->actingAs($admin)->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Subscription &amp; Billing', false)
            ->assertSee('Pay monthly');

        $this->assertSame('pro', PlatformSetting::current()->plan_tier, 'Paid plan from the hub is applied locally.');

        $this->actingAs($admin)->post(route('billing.pay'), ['plan' => 'pro', 'cycle' => 'yearly'])
            ->assertRedirect('https://mercury-uat.phonepe.com/transact/x');

        Http::assertSent(function (HttpRequest $r) {
            if (! str_contains($r->url(), '/api/payments/checkout')) {
                return false;
            }

            return $r['purpose'] === 'software_plan'
                && $r['item_key'] === 'pro'
                && $r['billing_cycle'] === 'yearly'
                && $r->header('X-Sync-Token')[0] === hash_hmac('sha256', $r->body(), self::LICENSE_KEY);
        });
    }

    public function test_client_growth_page_shows_pay_for_quoted_service_and_starts_checkout(): void
    {
        Config::set('libcontrol.license_server.enabled', false);
        Config::set('libcontrol.deployment.license_key', self::LICENSE_KEY);
        Config::set('libcontrol.deployment.sync_endpoint', 'https://hub.test/api/runtime/sync');

        Http::fake([
            'hub.test/api/payments/checkout' => Http::response(['redirect_url' => 'https://mercury-uat.phonepe.com/transact/y', 'payment' => []], 201),
            'hub.test/api/payments/summary' => Http::response(['plan' => [], 'payments' => [[
                'uuid' => '11111111-2222-3333-4444-555555555555', 'status' => 'completed', 'item_name' => 'Local SEO', 'amount_label' => '₹6,500',
            ]]]),
            '*' => Http::response(['tickets' => []]),
        ]);

        $branch = \App\Models\Branch::factory()->create();
        $order = GrowthOrder::query()->create([
            'branch_id' => $branch->id,
            'order_type' => 'service',
            'item_key' => 'local_seo',
            'item_name' => 'Local SEO',
            'status' => GrowthOrder::STATUS_QUOTED,
            'amount_paise' => 650000,
        ]);
        $admin = $this->admin(Admin::TYPE_CLIENT);

        $this->actingAs($admin)->get(route('growth.index', ['payment' => '11111111-2222-3333-4444-555555555555']))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->first()['pay_label'] === 'Pay ₹6,500'
                && $orders->first()['pay_url'] === route('growth.orders.pay', $order))
            ->assertOk();

        $this->actingAs($admin)->postJson(route('growth.orders.pay', $order))
            ->assertOk()
            ->assertJsonPath('checkout_url', 'https://mercury-uat.phonepe.com/transact/y');

        $order->update(['payment_status' => 'paid']);
        $this->actingAs($admin)->postJson(route('growth.orders.pay', $order))->assertStatus(422);
    }

    public function test_client_refuses_to_redirect_anywhere_but_phonepe(): void
    {
        Config::set('libcontrol.license_server.enabled', false);
        Config::set('libcontrol.deployment.license_key', self::LICENSE_KEY);
        Config::set('libcontrol.deployment.sync_endpoint', 'https://hub.test/api/runtime/sync');

        Http::fake([
            'hub.test/api/payments/checkout' => Http::response(['redirect_url' => 'https://phishing.test/pay'], 201),
            '*' => Http::response([]),
        ]);

        $this->actingAs($this->admin(Admin::TYPE_CLIENT))
            ->from(route('billing.index'))
            ->post(route('billing.pay'), ['plan' => 'starter', 'cycle' => 'monthly'])
            ->assertRedirect(route('billing.index'))
            ->assertSessionHasErrors('payment');
    }

    public function test_demo_site_never_takes_payments(): void
    {
        Config::set('libcontrol.license_server.enabled', false);
        Config::set('libcontrol.demo.enabled', true);
        Config::set('libcontrol.deployment.license_key', self::LICENSE_KEY);

        $this->actingAs($this->admin(Admin::TYPE_CLIENT))
            ->post(route('billing.pay'), ['plan' => 'pro', 'cycle' => 'monthly'])
            ->assertSessionHasErrors('payment');

        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '/api/payments/'));
    }

    public function test_demo_site_takes_payments_when_explicitly_enabled(): void
    {
        Config::set('libcontrol.license_server.enabled', false);
        Config::set('libcontrol.demo.enabled', true);
        Config::set('libcontrol.demo.payments_enabled', true);
        Config::set('libcontrol.deployment.license_key', self::LICENSE_KEY);
        Config::set('libcontrol.deployment.sync_endpoint', 'https://hub.test/api/runtime/sync');

        Http::fake([
            'hub.test/api/payments/checkout' => Http::response(['redirect_url' => 'https://mercury-uat.phonepe.com/transact/demo', 'payment' => []], 201),
            '*' => Http::response([]),
        ]);

        $this->actingAs($this->admin(Admin::TYPE_CLIENT))
            ->post(route('billing.pay'), ['plan' => 'pro', 'cycle' => 'monthly'])
            ->assertRedirect('https://mercury-uat.phonepe.com/transact/demo');
    }

    public function test_abandoned_checkout_resolves_when_the_library_opens_billing(): void
    {
        $deployment = $this->deployment();
        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan', 'item_key' => 'pro', 'billing_cycle' => 'monthly', 'domain' => 'client.test',
        ])->assertCreated();

        $this->signedPost('/api/payments/summary', ['domain' => 'client.test'])->assertOk();
        $this->assertSame(Payment::STATUS_PENDING, Payment::query()->sole()->status, 'Fresh checkouts are left alone.');

        $this->travel(2)->minutes();
        $this->remoteState = 'FAILED';

        $this->signedPost('/api/payments/summary', ['domain' => 'client.test'])
            ->assertOk()
            ->assertJsonPath('payments.0.status', Payment::STATUS_FAILED);

        $this->assertNotSame('pro', $deployment->fresh()->plan_tier);
    }

    public function test_paid_then_closed_tab_is_applied_on_next_billing_visit(): void
    {
        $deployment = $this->deployment();
        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan', 'item_key' => 'pro', 'billing_cycle' => 'monthly', 'domain' => 'client.test',
        ])->assertCreated();

        $this->travel(2)->minutes();
        $this->remoteState = 'COMPLETED';

        $this->signedPost('/api/payments/summary', ['domain' => 'client.test'])->assertOk();

        $this->assertSame('pro', $deployment->fresh()->plan_tier);
        $this->assertSame(Payment::STATUS_COMPLETED, Payment::query()->sole()->status);
    }

    public function test_webhook_arrival_is_recorded_on_the_payment(): void
    {
        $this->deployment();
        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan', 'item_key' => 'pro', 'billing_cycle' => 'monthly', 'domain' => 'client.test',
        ])->assertCreated();
        $payment = Payment::query()->sole();

        $this->remoteState = 'FAILED';
        $this->postJson('/api/webhooks/phonepe', [
            'event' => 'checkout.order.failed',
            'payload' => ['merchantOrderId' => $payment->merchant_order_id, 'state' => 'FAILED'],
        ], ['Authorization' => hash('sha256', 'hookuser:hookpass')])->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);
        $this->assertSame('checkout.order.failed', $payment->meta['webhook_event']);
        $this->assertNotNull($payment->webhookReceivedAt());
    }

    public function test_return_redirects_to_client_url_when_configured(): void
    {
        $this->deployment();
        $this->signedPost('/api/payments/checkout', [
            'purpose' => 'software_plan',
            'item_key' => 'pro',
            'billing_cycle' => 'monthly',
            'domain' => 'client.test',
            'return_url' => 'https://client.test/billing',
        ])->assertCreated();
        $uuid = Payment::query()->sole()->uuid;

        $this->get(route('payments.return', $uuid))
            ->assertRedirect('https://client.test/billing?payment='.$uuid);
    }

    public function test_developer_can_record_an_offline_paid_until_date(): void
    {
        $deployment = $this->deployment();
        $developer = $this->admin(Admin::TYPE_DEVELOPER);

        $this->actingAs($developer)
            ->post(route('developer.deployments.manage.plan', $deployment), [
                'plan_tier' => 'pro',
                'plan_paid_until' => now()->addMonths(3)->toDateString(),
            ])
            ->assertRedirect();

        $deployment->refresh();
        $this->assertSame('pro', $deployment->plan_tier);
        $this->assertSame(now()->addMonths(3)->toDateString(), $deployment->plan_paid_until->toDateString());

        $command = DeploymentCommand::query()->where('action', 'set_plan')->sole();
        $this->assertNotEmpty($command->payload['plan_paid_until']);

        $this->actingAs($developer)
            ->get(route('developer.deployments.manage', $deployment))
            ->assertOk()
            ->assertSee('Paid until')
            ->assertSee(now()->addMonths(3)->format('d M Y'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signedPost(string $uri, array $payload, string $licenseKey = self::LICENSE_KEY): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_LICENSE_KEY' => $licenseKey,
            'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, $licenseKey),
        ], $body);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function deployment(array $overrides = []): LicensedDeployment
    {
        return LicensedDeployment::query()->create(array_merge([
            'client_name' => 'Client Library',
            'license_key_hash' => LicensedDeployment::hashKey(self::LICENSE_KEY),
            'allowed_domains' => ['client.test'],
            'grace_days' => 7,
            'active' => true,
        ], $overrides));
    }

    private function admin(string $type): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create(['user_id' => $user->id, 'admin_type' => $type]);

        return $user;
    }
}
