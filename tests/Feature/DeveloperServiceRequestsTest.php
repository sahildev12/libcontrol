<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\GrowthOrder;
use App\Models\LibraryGrowthProfile;
use App\Models\LicensedDeployment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportTicketSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DeveloperServiceRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('libcontrol.license_server.enabled', true);
    }

    public function test_every_developer_page_renders_with_the_libcontrol_theme(): void
    {
        $developer = $this->developerAdmin();
        $deployment = $this->createDeployment();
        $ticket = $this->createTicket();
        $order = $this->createOrder(['support_ticket_id' => $ticket->id]);

        foreach ([
            route('developer.support-tickets.index'),
            route('developer.support-tickets.show', $ticket),
            route('developer.growth-orders.index'),
            route('developer.growth-orders.show', $order),
            route('developer.deployments.index'),
            route('developer.deployments.manage', $deployment),
            route('notifications.index'),
        ] as $url) {
            $this->actingAs($developer)->get($url)
                ->assertOk()
                ->assertSee('lc-theme', false)
                ->assertSee('Developer panel')
                ->assertSee('Service Requests');
        }
    }

    public function test_saving_a_deployment_plan_queues_the_update(): void
    {
        $deployment = $this->createDeployment();

        $this->actingAs($this->developerAdmin())
            ->post(route('developer.deployments.manage.plan', $deployment), [
                'plan_tier' => 'pro',
                'max_seats_override' => 120,
            ])
            ->assertRedirect(route('developer.deployments.manage', $deployment))
            ->assertSessionHasNoErrors();

        $this->assertSame('pro', $deployment->fresh()->plan_tier);
        $this->assertDatabaseHas('deployment_commands', [
            'licensed_deployment_id' => $deployment->id,
            'action' => 'set_plan',
        ]);
    }

    public function test_service_requests_list_can_be_filtered_and_searched(): void
    {
        $this->createOrder(['item_name' => 'Google Business Setup', 'library_name' => 'North Library']);
        $this->createOrder(['item_name' => 'Instagram Ads', 'library_name' => 'South Library', 'deployment_domain' => 'south.test', 'status' => GrowthOrder::STATUS_COMPLETED]);
        $developer = $this->developerAdmin();

        $this->actingAs($developer)->get(route('developer.growth-orders.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('Instagram Ads')
            ->assertDontSee('Google Business Setup');

        $this->actingAs($developer)->get(route('developer.growth-orders.index', ['q' => 'North']))
            ->assertOk()
            ->assertSee('Google Business Setup')
            ->assertDontSee('Instagram Ads');
    }

    public function test_updating_a_service_request_moves_the_linked_ticket(): void
    {
        $ticket = $this->createTicket();
        $order = $this->createOrder(['support_ticket_id' => $ticket->id]);

        $this->actingAs($this->developerAdmin())
            ->patch(route('developer.growth-orders.update', $order), [
                'status' => GrowthOrder::STATUS_ACTIVE,
                'payment_status' => 'paid',
                'admin_notes' => 'Work started today.',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(GrowthOrder::STATUS_ACTIVE, $order->status);
        $this->assertNotNull($order->activated_at);
        $this->assertSame(SupportTicket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertSame('Work started today.', $ticket->fresh()->admin_notes);
    }

    public function test_resent_ticket_keeps_service_request_status_and_pull_returns_it(): void
    {
        $licenseKey = LicensedDeployment::generateKey();
        $uuid = (string) Str::uuid();
        $orderUuid = (string) Str::uuid();
        $ticketPayload = [
            'uuid' => $uuid,
            'subject' => 'Growth order: Starter',
            'message' => "Type: package\nItem: Starter (starter)\nOrder UUID: {$orderUuid}",
            'reporter_email' => 'owner@library.test',
            'domain' => 'client.test',
        ];

        $this->signedPost('/api/support/tickets', $ticketPayload, $licenseKey)->assertOk();
        GrowthOrder::query()->where('uuid', $orderUuid)->firstOrFail()->update([
            'status' => GrowthOrder::STATUS_QUOTED,
            'admin_notes' => 'Quote sent.',
        ]);

        $this->signedPost('/api/support/tickets', $ticketPayload, $licenseKey)->assertOk();
        $this->assertSame(GrowthOrder::STATUS_QUOTED, GrowthOrder::query()->where('uuid', $orderUuid)->value('status'));

        $this->signedPost('/api/support/tickets/pull', ['uuids' => [$uuid]], $licenseKey)
            ->assertOk()
            ->assertJsonPath('tickets.0.service_request.status', GrowthOrder::STATUS_QUOTED)
            ->assertJsonPath('tickets.0.service_request.admin_notes', 'Quote sent.');
    }

    public function test_client_pull_applies_service_request_status_and_activates_package(): void
    {
        Config::set('libcontrol.license_server.enabled', false);
        Config::set('libcontrol.deployment.license_key', 'test-license-key-not-placeholder-123456');

        $branch = Branch::factory()->create();
        $ticket = $this->createTicket(['remote_id' => 7]);
        $order = $this->createOrder([
            'branch_id' => $branch->id,
            'support_ticket_id' => $ticket->id,
            'order_type' => 'package',
            'item_key' => 'grow',
        ]);

        Http::fake(['*' => Http::response(['tickets' => [[
            'uuid' => $ticket->uuid,
            'status' => SupportTicket::STATUS_IN_PROGRESS,
            'admin_notes' => 'Work started today.',
            'service_request' => [
                'status' => GrowthOrder::STATUS_ACTIVE,
                'payment_status' => 'paid',
                'admin_notes' => 'Work started today.',
                'monthly_report_url' => 'https://reports.test/october',
                'activated_at' => now()->toIso8601String(),
            ],
        ]]])]);

        app(SupportTicketSyncService::class)->pullUpdates();

        $order->refresh();
        $this->assertSame(GrowthOrder::STATUS_ACTIVE, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('Work started today.', $order->admin_notes);
        $this->assertSame('https://reports.test/october', $order->monthly_report_url);
        $this->assertNotNull($order->activated_at);
        $this->assertSame('grow', LibraryGrowthProfile::query()->where('branch_id', $branch->id)->value('active_package'));
        $this->assertSame(SupportTicket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signedPost(string $uri, array $payload, string $licenseKey): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_LICENSE_KEY' => $licenseKey,
            'HTTP_X_SYNC_TOKEN' => hash_hmac('sha256', $body, $licenseKey),
        ], $body);
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

    private function createDeployment(): LicensedDeployment
    {
        return LicensedDeployment::query()->create([
            'client_name' => 'North Library',
            'license_key_hash' => LicensedDeployment::hashKey(LicensedDeployment::generateKey()),
            'allowed_domains' => ['north.test'],
            'grace_days' => 7,
            'active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): SupportTicket
    {
        return SupportTicket::query()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'subject' => 'Growth order: Starter',
            'message' => 'Type: package',
            'reporter_name' => 'Jane Doe',
            'reporter_email' => 'jane@example.com',
            'status' => SupportTicket::STATUS_OPEN,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(array $overrides = []): GrowthOrder
    {
        return GrowthOrder::query()->create(array_merge([
            'order_type' => 'service',
            'item_key' => 'gbp_setup',
            'item_name' => 'Google Business Setup',
            'status' => GrowthOrder::STATUS_NEW,
            'contact_name' => 'Jane Doe',
            'contact_email' => 'jane@example.com',
            'library_name' => 'North Library',
            'deployment_domain' => 'north.test',
        ], $overrides));
    }
}
