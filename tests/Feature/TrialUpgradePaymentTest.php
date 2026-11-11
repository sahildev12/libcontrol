<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\Hosting\HostingProvider;
use App\Services\Tenancy\TenantConnectionManager;
use App\Services\Tenancy\TrialLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\Support\FakeHostingProvider;
use Tests\TestCase;

class TrialUpgradePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const HUB = 'http://libcontrol.in';

    private const LIBRARY = 'http://sunrise.libcontrol.in';

    private string $remoteState = 'PENDING';

    private ?int $remoteAmount = null;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', self::HUB);
        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.base_domain', 'libcontrol.in');
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.in']);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
        Config::set('libcontrol.trials.enabled', true);
        Config::set('libcontrol.license_server.enabled', true);
        Config::set('payments.trial_upgrade.test_amount_paise', 100);
        Config::set('payments.phonepe', array_merge(config('payments.phonepe'), [
            'env' => 'sandbox',
            'client_id' => 'TEST_CLIENT',
            'client_secret' => 'TEST_SECRET',
            'checkout_origin' => 'https://libcontrol.in',
            'webhook_username' => 'hookuser',
            'webhook_password' => 'hookpass',
        ]));

        $this->app->instance(HostingProvider::class, new FakeHostingProvider);
        RateLimiter::clear('trial-signup:127.0.0.1');
        Mail::fake();

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
                        'transactionId' => 'OM-TXN-TRIAL',
                        'paymentMode' => 'UPI_QR',
                        'state' => $this->remoteState,
                    ]],
                ]);
            }

            return null;
        });
    }

    protected function tearDown(): void
    {
        Config::set('database.default', 'sqlite');

        foreach (File::glob(database_path('lc_trial_*.sqlite')) as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    public function test_upgrade_url_points_at_tenant_billing(): void
    {
        $tenant = $this->readyTrial(signIn: false);

        $this->assertSame(
            'https://sunrise.libcontrol.in/billing',
            app(TrialLifecycle::class)->upgradeUrl($tenant),
        );
    }

    public function test_trial_checkout_charges_test_rupee_and_unlocks_on_completion(): void
    {
        $tenant = $this->readyTrial();

        $this->post(self::LIBRARY.'/billing/pay', [
            'plan' => 'pro',
            'cycle' => 'monthly',
        ])->assertRedirect();

        $payment = Payment::query()->sole();
        $this->assertSame($tenant->id, $payment->tenant_id);
        $this->assertSame(100, $payment->amount_paise);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame(self::LIBRARY.'/billing', $payment->return_url);

        $payCalls = collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (HttpRequest $r) => str_contains($r->url(), '/checkout/v2/pay'));
        $this->assertGreaterThan(0, $payCalls->count(), 'PhonePe pay was not called');
        $payRequest = $payCalls->first();
        $this->assertSame(100, $payRequest['amount']);
        $this->assertStringContainsString('/payments/', $payRequest['paymentFlow']['merchantUrls']['redirectUrl']);
        $this->assertStringContainsString('libcontrol.in', $payRequest['paymentFlow']['merchantUrls']['redirectUrl']);

        $this->remoteState = 'COMPLETED';
        $this->get('https://libcontrol.in/payments/'.$payment->uuid.'/return')
            ->assertRedirect();

        $tenant->refresh();
        $this->assertFalse($tenant->is_trial);
        $this->assertNull($tenant->locked_at);
        $this->assertNull($tenant->trial_ends_at);
        $this->assertSame('pro', $tenant->plan_tier);

        $paidUntil = app(TenantConnectionManager::class)->runOnTenant(
            $tenant,
            fn () => PlatformSetting::current()->plan_paid_until,
        );
        $this->assertNotNull($paidUntil);
        $this->assertTrue($paidUntil->between(now()->addMonth()->subMinute(), now()->addMonth()->addMinute()));

        $payment->refresh();
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertNotNull($payment->fulfilled_at);
    }

    public function test_billing_page_is_reachable_when_trial_is_read_only(): void
    {
        $tenant = $this->readyTrial();
        $tenant->forceFill([
            'trial_ends_at' => now()->subDay(),
            'locked_at' => now()->subDay(),
        ])->save();

        $this->get(self::LIBRARY.'/billing')
            ->assertOk()
            ->assertSee('Upgrade your library', false)
            ->assertSee('Test checkout is on', false)
            ->assertSee('₹1 test', false);
    }

    private function readyTrial(bool $signIn = true): Tenant
    {
        $this->post(self::HUB.'/try', [
            'library_name' => 'Sunrise Library',
            'subdomain' => 'sunrise',
            'owner_name' => 'Asha Rao',
            'email' => 'asha@example.com',
            'password' => 'secret-pass-1',
        ])->assertSessionDoesntHaveErrors();

        $tenant = Tenant::query()->where('subdomain', 'sunrise')->firstOrFail();
        $this->postJson(self::HUB.'/try/'.$tenant->signup_token.'/start')->assertOk();
        $this->assertSame(Tenant::STATUS_READY, $tenant->refresh()->status, (string) $tenant->provision_error);

        if ($signIn) {
            $redirect = $this->getJson(self::HUB.'/try/'.$tenant->signup_token.'/status')->json('redirect');
            $this->get($redirect)->assertRedirect();
            $this->assertNotNull(Auth::user());
        }

        app(TrialLifecycle::class)->markVerified($tenant->refresh());
        Config::set('database.default', 'sqlite');

        return $tenant;
    }
}
