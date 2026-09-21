<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeveloperSupportTicketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
    }

    public function test_index_shows_unread_indicator_for_new_tickets(): void
    {
        $user = $this->developerAdmin();
        $ticket = $this->createTicket(['read_at' => null]);

        $this->actingAs($user)
            ->get(route('developer.support-tickets.index'))
            ->assertOk()
            ->assertSee('supportTicketTable', false)
            ->assertSee($ticket->subject, false)
            ->assertSee('\u0022unread\u0022:true', false);
    }

    public function test_show_marks_ticket_as_read(): void
    {
        $user = $this->developerAdmin();
        $ticket = $this->createTicket(['read_at' => null]);

        $this->actingAs($user)
            ->get(route('developer.support-tickets.show', $ticket))
            ->assertOk();

        $this->assertNotNull($ticket->fresh()->read_at);
    }

    public function test_index_does_not_show_unread_indicator_after_ticket_is_read(): void
    {
        $user = $this->developerAdmin();
        $ticket = $this->createTicket(['read_at' => now()]);

        $this->actingAs($user)
            ->get(route('developer.support-tickets.index'))
            ->assertOk()
            ->assertSee($ticket->subject, false)
            ->assertSee('\u0022unread\u0022:false', false);
    }

    public function test_notification_feed_includes_unread_support_tickets(): void
    {
        $user = $this->developerAdmin();
        $ticket = $this->createTicket(['read_at' => null, 'subject' => 'Billing issue']);

        $this->actingAs($user)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('support_ticket_unread', 1)
            ->assertJsonPath('alerts.0.id', 'support_ticket:'.$ticket->id)
            ->assertJsonPath('alerts.0.title', 'New support ticket');
    }

    public function test_marking_support_ticket_notification_read_updates_ticket(): void
    {
        $user = $this->developerAdmin();
        $ticket = $this->createTicket(['read_at' => null]);

        $this->actingAs($user)
            ->postJson(route('notifications.mark-read'), [
                'keys' => ['support_ticket:'.$ticket->id],
            ])
            ->assertOk()
            ->assertJsonPath('support_ticket_unread', 0);

        $this->assertNotNull($ticket->fresh()->read_at);
    }

    public function test_admin_topbar_enables_support_ticket_notification_polling(): void
    {
        $user = $this->developerAdmin();
        $this->createTicket(['read_at' => null]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('notifications.feed'), false)
            ->assertSee('enableSound: true', false);
    }

    private function developerAdmin(): User
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): SupportTicket
    {
        return SupportTicket::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'subject' => 'Test support ticket',
            'message' => 'Need help with login.',
            'reporter_name' => 'Jane Doe',
            'reporter_email' => 'jane@example.com',
            'status' => SupportTicket::STATUS_OPEN,
        ], $overrides));
    }
}
