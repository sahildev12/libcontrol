<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HelpSupportAndWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_support_page_is_available(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('help-support.index'))
            ->assertOk()
            ->assertSee('Help &amp; Support', false)
            ->assertSee('Create a support ticket', false)
            ->assertSee('Get in touch', false)
            ->assertSee('Your recent tickets', false)
            ->assertSee('support-agent.png', false)
            ->assertSee('Support Articles', false)
            ->assertSee('Support Documentation', false)
            ->assertSee('libcontrol/support-articles.html', false)
            ->assertSee('libcontrol/documentation.html', false)
            ->assertSee('wa.me/918076105181', false);
    }

    public function test_user_can_create_support_ticket_when_phenomit_accepts_it(): void
    {
        config([
            'libcontrol.deployment.license_key' => 'ls_test_license_key_for_support_ticket',
            'libcontrol.deployment.sync_endpoint' => 'https://libcontrol.phenomit.com/api/runtime/sync',
        ]);

        Http::fake([
            'https://libcontrol.phenomit.com/api/support/tickets' => Http::response([
                'id' => 42,
                'uuid' => '00000000-0000-4000-8000-000000000001',
                'status' => 'open',
            ], 201),
        ]);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($user)->postJson(route('help-support.store'), [
            'subject' => 'Need help with fees',
            'message' => 'Fee setup is not saving correctly.',
            'category' => 'technical',
            'priority' => 'normal',
        ]);

        $response->assertCreated()
            ->assertJsonPath('ticket.subject', 'Need help with fees')
            ->assertJsonPath('ticket.synced', true);

        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'Need help with fees',
            'reporter_user_id' => $user->id,
            'remote_id' => 42,
        ]);

        Http::assertSentCount(1);
    }

    public function test_support_ticket_is_not_saved_when_phenomit_rejects_it(): void
    {
        config([
            'libcontrol.deployment.license_key' => 'ls_test_license_key_for_support_ticket',
            'libcontrol.deployment.sync_endpoint' => 'https://libcontrol.phenomit.com/api/runtime/sync',
        ]);

        Http::fake([
            'https://libcontrol.phenomit.com/api/support/tickets' => Http::response([
                'message' => 'Unauthorized.',
            ], 401),
        ]);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('help-support.store'), [
            'subject' => 'Need help with fees',
            'message' => 'Fee setup is not saving correctly.',
            'category' => 'technical',
            'priority' => 'normal',
        ])
            ->assertStatus(502)
            ->assertJsonFragment(['message' => 'Phenomit rejected this ticket (unauthorized). Verify LIBCONTROL_LICENSE_KEY matches the deployment on libcontrol.phenomit.com.']);

        $this->assertDatabaseMissing('support_tickets', [
            'subject' => 'Need help with fees',
        ]);
    }

    public function test_client_pulls_ticket_updates_from_phenomit_hub(): void
    {
        config([
            'libcontrol.deployment.license_key' => 'ls_test_license_key_for_support_ticket',
            'libcontrol.deployment.sync_endpoint' => 'https://libcontrol.phenomit.com/api/runtime/sync',
        ]);

        Http::fake([
            'https://libcontrol.phenomit.com/api/support/tickets/pull' => Http::response([
                'tickets' => [[
                    'uuid' => '00000000-0000-4000-8000-000000000099',
                    'status' => 'in_progress',
                    'admin_notes' => 'We are looking into this.',
                    'updated_at' => now()->toIso8601String(),
                ]],
            ], 200),
        ]);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        SupportTicket::query()->create([
            'uuid' => '00000000-0000-4000-8000-000000000099',
            'subject' => 'Need help with fees',
            'message' => 'Fee setup is not saving correctly.',
            'category' => 'technical',
            'priority' => 'normal',
            'status' => SupportTicket::STATUS_OPEN,
            'reporter_user_id' => $user->id,
            'reporter_name' => $user->name,
            'reporter_email' => $user->email,
            'remote_id' => 42,
            'synced_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('help-support.index'))
            ->assertOk()
            ->assertSee('In Progress', false)
            ->assertSee('We are looking into this.', false);

        $ticket = SupportTicket::query()->first();
        $this->assertSame(SupportTicket::STATUS_IN_PROGRESS, $ticket->status);
        $this->assertTrue($ticket->client_update_pending);
    }

    public function test_support_ticket_is_not_saved_without_real_license_key(): void
    {
        config([
            'libcontrol.deployment.license_key' => 'your_license_key_from_phenomit',
        ]);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)->postJson(route('help-support.store'), [
            'subject' => 'Need help with fees',
            'message' => 'Fee setup is not saving correctly.',
            'category' => 'technical',
            'priority' => 'normal',
        ])
            ->assertStatus(502);

        $this->assertDatabaseMissing('support_tickets', [
            'subject' => 'Need help with fees',
        ]);
    }

    public function test_home_shows_library_website_even_when_website_setting_disabled(): void
    {
        PlatformSetting::query()->firstOrCreate([], [
            'website_enabled' => false,
            'display_name' => 'Demo Library',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Demo Library', false)
            ->assertSee('Branch login', false);
    }

    public function test_home_shows_configured_website_content(): void
    {
        $settings = PlatformSetting::query()->firstOrCreate([], []);
        $settings->update([
            'website_enabled' => true,
            'website_hero_title' => 'Welcome to Demo Library',
            'website_about' => 'A quiet place to study.',
            'website_amenities' => ['Wi-Fi', 'AC'],
            'website_whatsapp' => '9876543210',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Welcome to Demo Library', false)
            ->assertSee('Wi-Fi', false)
            ->assertSee('Branch login', false);
    }

    public function test_authenticated_user_is_redirected_from_home_to_dashboard(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_legacy_library_url_redirects_to_home(): void
    {
        $this->get('/library')->assertRedirect(route('home'));
    }

    public function test_platform_admin_can_update_email_notification_settings(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => 'client',
        ]);

        $this->actingAs($user)
            ->patchJson(route('settings.email-notifications.update'), [
                'email_welcome_enabled' => true,
                'email_birthday_enabled' => false,
                'email_offers_enabled' => true,
                'email_recovery_enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('email_notifications.email_birthday_enabled', false);
    }
}
