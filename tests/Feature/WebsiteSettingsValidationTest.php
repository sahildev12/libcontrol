<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteSettingsValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_whatsapp_and_social_links_are_rejected(): void
    {
        $this->actingAs($this->platformAdmin())
            ->postJson(route('settings.website.update'), [
                'website_whatsapp' => 'gadgsdfgdf',
                'website_social_links' => [
                    'facebook' => 'https://instagram.com/demo',
                    'instagram' => 'not a link',
                    'youtube' => 'https://example.com/video',
                    'twitter' => 'https://facebook.com/demo',
                    'website' => 'javascript:alert(1)',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'website_social_links.facebook',
                'website_social_links.instagram',
                'website_social_links.youtube',
                'website_social_links.twitter',
                'website_social_links.website',
            ]);
    }

    public function test_short_whatsapp_number_is_rejected(): void
    {
        $this->actingAs($this->platformAdmin())
            ->postJson(route('settings.website.update'), ['website_whatsapp' => '98765'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['website_whatsapp']);
    }

    public function test_links_without_scheme_and_formatted_whatsapp_are_normalised(): void
    {
        $this->actingAs($this->platformAdmin())
            ->postJson(route('settings.website.update'), [
                'website_whatsapp' => '+91 98765-43210',
                'website_social_links' => [
                    'facebook' => 'facebook.com/sunrise',
                    'instagram' => 'https://www.instagram.com/sunrise/',
                    'youtube' => 'youtu.be/abc123',
                    'twitter' => 'x.com/sunrise',
                    'website' => 'sunrise-library.in',
                ],
            ])
            ->assertOk();

        $settings = PlatformSetting::current();
        $this->assertSame('9876543210', $settings->website_whatsapp);
        $this->assertSame('https://facebook.com/sunrise', $settings->website_social_links['facebook']);
        $this->assertSame('https://youtu.be/abc123', $settings->website_social_links['youtube']);
        $this->assertSame('https://sunrise-library.in', $settings->website_social_links['website']);
    }

    public function test_changing_saved_social_links_and_amenities_saves_on_first_attempt(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->postJson(route('settings.website.update'), [
                'website_amenities' => ['Wi-Fi'],
                'website_social_links' => ['facebook' => 'https://facebook.com/old'],
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson(route('settings.website.update'), [
                'website_amenities' => ['Wi-Fi', 'AC'],
                'website_social_links' => [
                    'facebook' => 'https://www.facebook.com/libcontrol',
                    'instagram' => 'https://www.instagram.com/?hl=en',
                    'youtube' => 'https://www.youtube.com/watch?v=HlwAI05Y1fU&list=RD4b8Xvjl7oew&index=11',
                    'twitter' => 'https://x.com/libcontrol',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Website settings saved.');

        $this->assertDatabaseHas('activity_logs', ['action' => 'platform_setting.updated']);
    }

    public function test_empty_contact_fields_are_allowed(): void
    {
        $this->actingAs($this->platformAdmin())
            ->postJson(route('settings.website.update'), [
                'website_whatsapp' => '',
                'website_social_links' => ['facebook' => '', 'website' => ''],
            ])
            ->assertOk();

        $this->assertNull(PlatformSetting::current()->website_whatsapp);
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        return $user;
    }
}
