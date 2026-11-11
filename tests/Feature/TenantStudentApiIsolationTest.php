<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\Tenant;
use App\Services\Hosting\HostingProvider;
use App\Services\Tenancy\TenantConnectionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\FakeHostingProvider;
use Tests\TestCase;

class TenantStudentApiIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const HUB = 'http://libcontrol.in';

    private const LIBRARY = 'http://sunrise.libcontrol.in';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', self::HUB);
        Config::set('libcontrol.tenancy.enabled', true);
        Config::set('libcontrol.tenancy.base_domain', 'libcontrol.in');
        Config::set('libcontrol.tenancy.landlord_hosts', ['libcontrol.in']);
        Config::set('libcontrol.tenancy.landlord_connection', 'sqlite');
        Config::set('libcontrol.trials.enabled', true);

        $this->app->instance(HostingProvider::class, new FakeHostingProvider);
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

    public function test_student_api_on_tenant_host_uses_tenant_database_not_landlord(): void
    {
        $tenant = $this->readyTrial();

        app(TenantConnectionManager::class)->runOnTenant($tenant, function (): void {
            PlatformSetting::query()->create([
                'student_code_prefix' => 'SUN',
                'student_code_padding' => 3,
            ]);
            $branch = Branch::factory()->create(['name' => 'Sunrise Main']);
            Student::factory()->create([
                'branch_id' => $branch->id,
                'student_code' => 'SUN-101',
                'status' => 'active',
                'app_pin_hash' => null,
            ]);
        });

        // Landlord must not see the tenant-only student.
        PlatformSetting::query()->create([
            'student_code_prefix' => 'HUB',
            'student_code_padding' => 3,
        ]);
        $this->assertNull(Student::query()->where('student_code', 'SUN-101')->first());

        $this->postJson(self::LIBRARY.'/api/v1/student/auth/check-code', [
            'student_code' => 'SUN-101',
        ])
            ->assertOk()
            ->assertJsonPath('student.student_code', 'SUN-101')
            ->assertJsonPath('needs_pin_setup', true);

        $this->postJson(self::HUB.'/api/v1/student/auth/check-code', [
            'student_code' => 'SUN-101',
        ])->assertStatus(422);
    }

    private function readyTrial(): Tenant
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

        Config::set('database.default', 'sqlite');

        return $tenant;
    }
}
