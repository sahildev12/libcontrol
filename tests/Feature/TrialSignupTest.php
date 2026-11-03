<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Hosting\HostingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeHostingProvider;
use Tests\TestCase;

class TrialSignupTest extends TestCase
{
    use RefreshDatabase;

    private const HUB = 'http://libcontrol.in';

    private FakeHostingProvider $hosting;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.base_domain', 'libcontrol.in');
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.in']);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
        Config::set('libcontrol.trials.enabled', true);

        $this->hosting = new FakeHostingProvider;
        $this->app->instance(HostingProvider::class, $this->hosting);
        RateLimiter::clear('trial-signup:127.0.0.1');
    }

    protected function tearDown(): void
    {
        Config::set('database.default', 'sqlite');

        foreach (File::glob(database_path('lc_trial_*.sqlite')) as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    private function signUp(array $overrides = []): TestResponse
    {
        return $this->post(self::HUB.'/try', array_merge([
            'library_name' => 'Sunrise Library',
            'subdomain' => 'sunrise',
            'email' => 'asha@example.com',
            'password' => 'secret-pass-1',
        ], $overrides));
    }

    public function test_signup_page_is_only_available_when_trials_are_enabled(): void
    {
        $this->get(self::HUB.'/try')->assertOk()->assertSee('Create my library')->assertSee('.libcontrol.in');

        Config::set('libcontrol.trials.enabled', false);

        $this->get(self::HUB.'/try')->assertNotFound();
    }

    public function test_subdomain_availability_check(): void
    {
        $this->getJson(self::HUB.'/try/check?subdomain=Green Valley')
            ->assertOk()
            ->assertJson(['available' => true, 'subdomain' => 'green-valley']);

        $this->getJson(self::HUB.'/try/check?subdomain=demo')
            ->assertOk()
            ->assertJson(['available' => false, 'suggestion' => 'demo-2']);

        $this->signUp();
        $this->getJson(self::HUB.'/try/check?subdomain=sunrise')
            ->assertJson(['available' => false, 'suggestion' => 'sunrise-2']);
        $this->getJson(self::HUB.'/try/check?subdomain=ab')
            ->assertJson(['available' => false, 'suggestion' => null]);
    }

    public function test_signup_builds_the_library_and_hands_the_owner_a_one_time_login(): void
    {
        $response = $this->signUp();
        $tenant = Tenant::query()->where('subdomain', 'sunrise')->firstOrFail();

        $response->assertRedirect(route('trials.building', $tenant->signup_token));
        $this->assertSame(Tenant::STATUS_PENDING, $tenant->status);

        $this->get(self::HUB.'/try/'.$tenant->signup_token)->assertOk()->assertSee('Building Sunrise Library');

        $this->postJson(self::HUB.'/try/'.$tenant->signup_token.'/start')->assertOk();
        $this->assertSame(Tenant::STATUS_READY, $tenant->refresh()->status, (string) $tenant->provision_error);

        $status = $this->getJson(self::HUB.'/try/'.$tenant->signup_token.'/status')
            ->assertOk()
            ->assertJson(['status' => 'ready', 'steps' => ['subdomain' => 'done', 'ssl' => 'done']])
            ->json();

        $this->assertMatchesRegularExpression('#^http://sunrise\.libcontrol\.in/trial/enter/[A-Za-z0-9]{64}$#', $status['redirect']);

        $this->get($status['redirect'])->assertRedirect();
        $this->assertSame('asha@example.com', Auth::user()?->email);

        Auth::logout();
        $this->get($status['redirect'])->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_build_does_not_attribute_changes_to_a_user_signed_in_on_the_main_site(): void
    {
        User::factory()->count(3)->create();
        $this->actingAs(User::factory()->create());

        $this->signUp();
        $tenant = Tenant::query()->where('subdomain', 'sunrise')->firstOrFail();
        $this->postJson(self::HUB.'/try/'.$tenant->signup_token.'/start')->assertOk();

        $this->assertSame(Tenant::STATUS_READY, $tenant->refresh()->status, (string) $tenant->provision_error);
    }

    public function test_status_without_the_signup_session_points_to_the_login_page(): void
    {
        $this->signUp();
        $tenant = Tenant::query()->where('subdomain', 'sunrise')->firstOrFail();
        $this->postJson(self::HUB.'/try/'.$tenant->signup_token.'/start');

        $this->flushSession();

        $this->getJson(self::HUB.'/try/'.$tenant->signup_token.'/status')
            ->assertJson(['status' => 'ready', 'redirect' => 'http://sunrise.libcontrol.in/admin/login']);
    }

    public function test_bots_duplicate_emails_and_repeat_signups_are_blocked(): void
    {
        $this->signUp(['website' => 'http://spam.test'])->assertSessionHasErrors('email');
        $this->assertSame(0, Tenant::query()->count());

        $this->signUp()->assertSessionDoesntHaveErrors();
        $this->signUp(['subdomain' => 'other'])->assertSessionHasErrors('email');

        Config::set('libcontrol.trials.signups_per_ip_per_day', 2);
        $this->signUp(['subdomain' => 'third', 'email' => 'b@example.com'])->assertSessionDoesntHaveErrors();
        $this->signUp(['subdomain' => 'fourth', 'email' => 'c@example.com'])->assertSessionHasErrors('email');
        $this->assertSame(2, Tenant::query()->count());
    }

    public function test_resume_command_builds_trials_the_page_never_started(): void
    {
        $this->signUp();
        $tenant = Tenant::query()->where('subdomain', 'sunrise')->firstOrFail();

        $this->artisan('libcontrol:trial-resume')->assertSuccessful();
        $this->assertSame(Tenant::STATUS_PENDING, $tenant->refresh()->status);

        $this->travel(2)->minutes();
        $this->artisan('libcontrol:trial-resume')->assertSuccessful();
        $this->assertSame(Tenant::STATUS_READY, $tenant->refresh()->status);
    }
}
