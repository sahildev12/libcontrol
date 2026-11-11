<?php

namespace Tests\Feature;

use App\Mail\AutomationAlertMail;
use App\Models\EmailAutomationLog;
use App\Models\Tenant;
use App\Services\Hosting\HostingProvider;
use App\Services\Tenancy\TrialLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\FakeHostingProvider;
use Tests\TestCase;

class TrialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const HUB = 'http://libcontrol.in';

    private const LIBRARY = 'http://sunrise.libcontrol.in';

    private FakeHostingProvider $hosting;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', self::HUB);
        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.base_domain', 'libcontrol.in');
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.in']);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
        Config::set('libcontrol.trials.enabled', true);

        $this->hosting = new FakeHostingProvider;
        $this->app->instance(HostingProvider::class, $this->hosting);
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

        Config::set('database.default', 'sqlite');

        return $tenant;
    }

    /**
     * @return list<string>
     */
    private function sentSubjects(): array
    {
        return Mail::sent(AutomationAlertMail::class)->map(fn (AutomationAlertMail $mail) => $mail->subjectLine)->values()->all();
    }

    public function test_signup_emails_a_verification_link_that_verifies_the_owner(): void
    {
        $tenant = $this->readyTrial(signIn: false);

        $mail = Mail::sent(AutomationAlertMail::class, fn (AutomationAlertMail $mail) => str_contains((string) $mail->ctaUrl, '/try/verify/'))->first();
        $this->assertNotNull($mail);
        $this->assertTrue($mail->hasTo('asha@example.com'));
        $this->assertStringStartsWith(self::HUB.'/try/verify/'.$tenant->id.'/', $mail->ctaUrl);

        $this->get(str_replace('signature=', 'signature=0', $mail->ctaUrl))
            ->assertOk()
            ->assertSee('This link has expired');
        $this->assertNull($tenant->refresh()->owner_email_verified_at);

        $this->get($mail->ctaUrl)->assertOk()->assertSee('Email verified')->assertSee('Open my library');
        $this->assertNotNull($tenant->refresh()->owner_email_verified_at);
    }

    public function test_ready_library_gets_a_welcome_email_with_login_url(): void
    {
        Config::set('libcontrol.public_url', self::HUB);
        $tenant = $this->readyTrial(signIn: false);

        $welcome = Mail::sent(
            AutomationAlertMail::class,
            fn (AutomationAlertMail $mail) => $mail->subjectLine === 'Welcome to LibControl — Sunrise Library is ready'
        )->first();

        $this->assertNotNull($welcome);
        $this->assertTrue($welcome->hasTo('asha@example.com'));
        $this->assertSame(self::HUB.'/admin/login', $welcome->ctaUrl);
        $this->assertSame('Log in to your library', $welcome->ctaLabel);
        $this->assertStringContainsString(self::HUB.'/admin/login', $welcome->bodyText);
        $this->assertStringContainsString('https://sunrise.libcontrol.in', $welcome->bodyText);
        $this->assertTrue(
            EmailAutomationLog::query()
                ->where('related_id', $tenant->id)
                ->where('template_key', 'trial_welcome')
                ->exists()
        );

        Mail::fake();
        app(TrialLifecycle::class)->sendWelcome($tenant->refresh());
        Mail::assertNothingSent();
    }

    public function test_unverified_library_is_paused_after_the_window_until_the_owner_verifies(): void
    {
        $tenant = $this->readyTrial();

        $this->get(self::LIBRARY.'/profile-completion')->assertDontSee('Verify your email to continue');

        $this->travel(25)->hours();

        $this->get(self::LIBRARY.'/profile-completion')->assertForbidden()->assertSee('Verify your email to continue');
        $this->postJson(self::LIBRARY.'/branch', [])->assertForbidden();

        Mail::fake();
        $this->post(self::LIBRARY.'/trial/verify/resend')->assertRedirect();
        Mail::assertSent(AutomationAlertMail::class, fn (AutomationAlertMail $mail) => str_contains($mail->ctaUrl, '/try/verify/'));

        Config::set('database.default', 'sqlite');
        app(TrialLifecycle::class)->markVerified($tenant->refresh());

        $this->get(self::LIBRARY.'/profile-completion')->assertDontSee('Verify your email to continue');
    }

    public function test_ended_trial_is_read_only(): void
    {
        $tenant = $this->readyTrial();
        app(TrialLifecycle::class)->markVerified($tenant);

        $this->travel(16)->days();

        $this->get(self::LIBRARY.'/profile-completion')->assertOk()->assertSee('Your free trial has ended');
        $this->postJson(self::LIBRARY.'/branch', ['name' => 'Second'])->assertStatus(423);
        $this->from(self::LIBRARY.'/profile-completion')
            ->post(self::LIBRARY.'/profile-completion/continue')
            ->assertRedirect(self::LIBRARY.'/profile-completion')
            ->assertSessionHas('trial_read_only');

        $this->post(self::LIBRARY.'/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_lifecycle_reminds_locks_warns_and_finally_deletes_an_unpaid_trial(): void
    {
        $tenant = $this->readyTrial(signIn: false);
        app(TrialLifecycle::class)->markVerified($tenant);
        $database = $tenant->database_name;
        Mail::fake();

        $this->travel(12)->days();
        $this->travel(12)->hours();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->assertSame(['Your LibControl trial ends in 3 days'], $this->sentSubjects());

        $this->travel(2)->days();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->assertContains('Your LibControl trial ends tomorrow', $this->sentSubjects());

        $this->travel(1)->days();
        $this->artisan('libcontrol:trial-lifecycle --dry-run')->expectsOutputToContain('locked (read-only)')->assertSuccessful();
        $this->assertNull($tenant->refresh()->locked_at);

        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->assertNotNull($tenant->refresh()->locked_at);
        $this->assertContains('Your LibControl trial has ended', $this->sentSubjects());

        $this->travel(16)->days();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->travel(11)->days();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->assertSame(2, collect($this->sentSubjects())->filter(fn ($subject) => str_contains($subject, 'will be deleted on'))->count());
        $this->assertSame(Tenant::STATUS_READY, $tenant->refresh()->status);

        $this->travel(3)->days();
        $this->travel(1)->hours();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();

        $this->assertNull(Tenant::query()->find($tenant->id));
        $this->assertContains($database, $this->hosting->deletedDatabases);
        $this->assertContains('sunrise', $this->hosting->deletedSubdomains);
        $this->assertTrue(EmailAutomationLog::query()->where('related_id', $tenant->id)->where('template_key', 'like', 'trial_delete_3d:%')->exists());
    }

    public function test_unverified_owner_gets_a_reminder_then_a_paused_notice(): void
    {
        $this->readyTrial(signIn: false);
        Mail::fake();

        $this->travel(13)->hours();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->assertSame(['Reminder: verify your email for Sunrise Library'], $this->sentSubjects());

        $this->travel(12)->hours();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->artisan('libcontrol:trial-lifecycle')->assertSuccessful();
        $this->assertSame(['Reminder: verify your email for Sunrise Library', 'Your library is paused: please verify your email'], $this->sentSubjects());
    }
}
