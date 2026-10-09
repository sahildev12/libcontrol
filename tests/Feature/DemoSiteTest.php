<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\User;
use Database\Seeders\DemoSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_pages_hide_demo_credentials_by_default(): void
    {
        Config::set('libcontrol.demo.enabled', false);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertDontSee('data-demo-login', false);
    }

    public function test_demo_mode_shows_matching_credentials_on_each_portal(): void
    {
        Config::set('libcontrol.demo.enabled', true);
        Config::set('libcontrol.demo.admin_email', 'admin@demo.libcontrol.in');
        Config::set('libcontrol.demo.admin_password', 'Demo@1234');
        Config::set('libcontrol.demo.branch_email', 'branch@demo.libcontrol.in');
        Config::set('libcontrol.demo.branch_password', 'Demo@5678');

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('data-demo-login', false)
            ->assertSee('admin@demo.libcontrol.in')
            ->assertSee('Demo@1234')
            ->assertDontSee('branch@demo.libcontrol.in');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('branch@demo.libcontrol.in')
            ->assertSee('Demo@5678');

        $this->get(route('developer.login'))
            ->assertOk()
            ->assertDontSee('data-demo-login', false);
    }

    public function test_demo_seeder_creates_working_demo_logins_without_developer_account(): void
    {
        Config::set('libcontrol.demo.admin_email', 'admin@demo.libcontrol.in');
        Config::set('libcontrol.demo.admin_password', 'Demo@1234');
        Config::set('libcontrol.demo.branch_email', 'branch@demo.libcontrol.in');
        Config::set('libcontrol.demo.branch_password', 'Demo@1234');

        $this->seed(DemoSiteSeeder::class);

        $admin = User::query()->where('email', 'admin@demo.libcontrol.in')->firstOrFail();
        $this->assertSame(Admin::TYPE_CLIENT, $admin->adminProfile?->admin_type);
        $this->assertNull($admin->branch_id);

        $branchUser = User::query()->where('email', 'branch@demo.libcontrol.in')->firstOrFail();
        $this->assertNotNull($branchUser->branch_id);

        $this->assertSame(0, Admin::query()->where('admin_type', Admin::TYPE_DEVELOPER)->count());
        $this->assertSame('pro', PlatformSetting::current()->plan_tier);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@demo.libcontrol.in',
            'password' => 'Demo@1234',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }
}
