<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\Hosting\HostingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\Support\FakeHostingProvider;
use Tests\TestCase;

class TrialSocialAuthTest extends TestCase
{
    use RefreshDatabase;

    private const HUB = 'http://libcontrol.in';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', self::HUB);
        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.base_domain', 'libcontrol.in');
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.in']);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
        Config::set('libcontrol.trials.enabled', true);
        foreach (['google', 'facebook'] as $provider) {
            Config::set("services.{$provider}.client_id", "{$provider}-id");
            Config::set("services.{$provider}.client_secret", "{$provider}-secret");
        }

        $this->app->instance(HostingProvider::class, new FakeHostingProvider);
        RateLimiter::clear('trial-signup:127.0.0.1');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Config::set('database.default', 'sqlite');

        foreach (File::glob(database_path('lc_trial_*.sqlite')) as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    private function fakeAccount(string $provider, string $id, ?string $email, bool $verified = true): void
    {
        $user = (new SocialiteUser)
            ->setRaw(['email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Asha Rao', 'email' => $email]);

        $driver = Mockery::mock(AbstractProvider::class);
        $driver->shouldReceive('scopes', 'with')->andReturnSelf();
        $driver->shouldReceive('redirect')->andReturn(new RedirectResponse("https://{$provider}.test/oauth"));
        $driver->shouldReceive('user')->andReturn($user);

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with($provider)->andReturn($driver);
        Socialite::swap($socialite);
    }

    private function oauthCallback(string $provider): TestResponse
    {
        return $this->get(self::HUB."/auth/{$provider}/callback?code=abc&state=xyz");
    }

    private function signUpWith(string $provider, string $id, string $email, bool $verified = true): Tenant
    {
        $this->fakeAccount($provider, $id, $email, $verified);
        $this->oauthCallback($provider)->assertRedirect(route('trials.create'));

        $this->post(self::HUB.'/try', [
            'library_name' => 'Sunrise Library',
            'subdomain' => 'sunrise',
        ])->assertSessionDoesntHaveErrors();

        return Tenant::query()->where('subdomain', 'sunrise')->firstOrFail();
    }

    private function build(Tenant $tenant): Tenant
    {
        $this->postJson(self::HUB.'/try/'.$tenant->signup_token.'/start')->assertOk();
        $this->assertSame(Tenant::STATUS_READY, $tenant->refresh()->status, (string) $tenant->provision_error);

        return $tenant;
    }

    public function test_buttons_only_show_for_configured_providers(): void
    {
        $this->get(self::HUB.'/try')
            ->assertOk()
            ->assertSee('data-social-provider="google"', false)
            ->assertSee('data-social-provider="facebook"', false);

        Config::set('services.facebook.client_secret', null);

        $this->get(self::HUB.'/try')
            ->assertSee('data-social-provider="google"', false)
            ->assertDontSee('data-social-provider="facebook"', false);
        $this->get(self::HUB.'/auth/facebook/redirect')->assertNotFound();
    }

    public function test_google_signup_skips_email_and_password_and_is_verified(): void
    {
        $this->fakeAccount('google', 'g-1', 'Asha@Example.com');
        $this->oauthCallback('google')->assertRedirect(route('trials.create'));

        $this->get(self::HUB.'/try')
            ->assertSee('Signed in with Google')
            ->assertSee('asha@example.com')
            ->assertDontSee('Choose a password');

        $this->post(self::HUB.'/try', [
            'library_name' => 'Sunrise Library',
            'subdomain' => 'sunrise',
        ])->assertSessionDoesntHaveErrors();

        $tenant = Tenant::query()->where('subdomain', 'sunrise')->firstOrFail();
        $this->assertSame('asha@example.com', $tenant->owner_email);
        $this->assertSame('Asha Rao', $tenant->owner_name);
        $this->assertSame('google', $tenant->signup_source);
        $this->assertSame('g-1', $tenant->owner_social_id);
        $this->assertNotNull($tenant->owner_email_verified_at);
        Mail::assertNothingSent();

        $status = $this->build($tenant);
        $redirect = $this->getJson(self::HUB.'/try/'.$status->signup_token.'/status')->json('redirect');
        $this->get($redirect)->assertRedirect();
        $this->assertSame('asha@example.com', Auth::user()?->email);
    }

    public function test_facebook_signup_still_requires_email_verification(): void
    {
        $tenant = $this->signUpWith('facebook', 'fb-1', 'asha@example.com');

        $this->assertSame('facebook', $tenant->signup_source);
        $this->assertNull($tenant->owner_email_verified_at);
        Mail::assertSentCount(1);
    }

    public function test_returning_owner_is_signed_into_their_library(): void
    {
        $tenant = $this->build($this->signUpWith('google', 'g-1', 'asha@example.com'));
        $this->flushSession();

        $this->fakeAccount('google', 'g-1', 'asha@example.com');
        $location = $this->oauthCallback('google')->headers->get('Location');

        $this->assertMatchesRegularExpression('#^http://sunrise\.libcontrol\.in/trial/enter/[A-Za-z0-9]{64}$#', (string) $location);
        $this->assertNotNull($tenant->refresh()->login_token_hash);
    }

    public function test_library_login_requires_the_owner_account(): void
    {
        $this->build($this->signUpWith('facebook', 'fb-1', 'asha@example.com'));
        $this->flushSession();

        $this->get(self::HUB.'/auth/facebook/redirect?library=sunrise');
        $this->fakeAccount('facebook', 'fb-other', 'asha@example.com');
        $this->oauthCallback('facebook')->assertRedirect('http://sunrise.libcontrol.in/admin/login?signin=not-owner');

        $this->get(self::HUB.'/auth/facebook/redirect?library=sunrise');
        $this->fakeAccount('facebook', 'fb-1', 'asha@example.com');
        $location = $this->oauthCallback('facebook')->headers->get('Location');
        $this->assertStringStartsWith('http://sunrise.libcontrol.in/trial/enter/', (string) $location);
    }

    public function test_unverified_google_email_does_not_unlock_an_existing_library(): void
    {
        $this->build($this->signUpWith('facebook', 'fb-1', 'asha@example.com'));
        $this->flushSession();

        $this->get(self::HUB.'/auth/google/redirect?library=sunrise');
        $this->fakeAccount('google', 'g-9', 'asha@example.com', verified: false);
        $this->oauthCallback('google')->assertRedirect('http://sunrise.libcontrol.in/admin/login?signin=not-owner');
    }

    public function test_cancelled_or_emailless_sign_in_returns_to_the_form(): void
    {
        $this->get(self::HUB.'/auth/google/callback?error=access_denied')
            ->assertRedirect(route('trials.create'))
            ->assertSessionHas('trial_social_error');

        $this->fakeAccount('facebook', 'fb-2', null);
        $this->oauthCallback('facebook')
            ->assertRedirect(route('trials.create'))
            ->assertSessionHas('trial_social_error');
        $this->assertNull(session('trial_social'));
    }
}
