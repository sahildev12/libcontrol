<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_admin_login_page_is_separate_from_branch_login(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk()->assertSee('Admin login');
        $this->get(route('login'))->assertOk()->assertSee('Branch login');
    }

    public function test_platform_admin_cannot_use_branch_login(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        \App\Models\Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => \App\Models\Admin::TYPE_DEVELOPER,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_platform_admin_can_authenticate_on_admin_login(): void
    {
        \App\Models\Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => null]);
        \App\Models\Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => \App\Models\Admin::TYPE_DEVELOPER,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_branch_login_shows_branch_name_when_only_one_branch_exists(): void
    {
        \App\Models\Branch::factory()->create(['name' => 'North Library Center']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('North Library Center');
    }

    public function test_branch_login_shows_uploaded_library_logo_when_several_branches_exist(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $path = 'branches/1/brand/logo.png';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'logo');

        \App\Models\Branch::factory()->create([
            'name' => 'Main Library Center',
            'display_name' => 'Phenom Lib',
            'logo_with_text_path' => $path,
        ]);
        \App\Models\Branch::factory()->create(['name' => 'North Branch Center']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Phenom Lib')
            ->assertSee('/storage/'.$path, false);
    }
}
