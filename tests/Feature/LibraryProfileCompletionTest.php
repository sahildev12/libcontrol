<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\Profile\LibraryProfileCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_amenities_alone_raise_profile_completion_above_zero(): void
    {
        PlatformSetting::current()->update([
            'website_amenities' => ['Wi-Fi', 'CCTV', 'Full AC'],
        ]);

        $score = app(LibraryProfileCompletionService::class)->scoreForLibrary(null);

        $this->assertSame(25, $score['pct']);
        $this->assertSame('ok', collect($score['items'])->firstWhere('key', 'amenities')['status']);
    }

    public function test_website_save_returns_updated_profile_completion(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->postJson(route('settings.website.update'), [
                'website_amenities' => ['Wi-Fi', 'Reading Room'],
                'website_address' => '12 MG Road, Hisar, Haryana 125001',
            ])
            ->assertOk()
            ->assertJsonPath('profile_completion.pct', 25)
            ->assertJsonPath('profile_completion.complete', false)
            ->assertJsonPath('website.website_address', '12 MG Road, Hisar, Haryana 125001');
    }

    public function test_platform_admin_can_preview_website_while_logged_in(): void
    {
        PlatformSetting::current()->update([
            'website_enabled' => false,
            'display_name' => 'Sunrise Study Hall',
        ]);

        $this->actingAs($this->platformAdmin())
            ->get(route('website.preview'))
            ->assertOk()
            ->assertSee('Preview mode', false)
            ->assertSee('Sunrise Study Hall', false)
            ->assertSee('Your public website is still off', false);
    }

    private function platformAdmin(): User
    {
        Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        return $user;
    }
}
