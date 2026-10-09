<?php

namespace App\Services\Growth;

use App\Mail\GrowthOrderRequestedMail;
use App\Models\Branch;
use App\Models\GrowthOrder;
use App\Models\LibraryGrowthProfile;
use App\Models\PlatformSetting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportTicketSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GrowthOrderService
{
    public function __construct(
        private SupportTicketSyncService $ticketSync,
    ) {}

    /**
     * @param  array{
     *     order_type: string,
     *     item_key: string,
     *     message?: string|null,
     *     contact_name?: string|null,
     *     contact_email?: string|null,
     *     contact_phone?: string|null,
     * }  $input
     * @return array{order: GrowthOrder, ticket_synced: bool, ticket_error: string|null, whatsapp_url: string}
     */
    public function request(User $user, ?Branch $branch, array $input): array
    {
        $catalog = $this->resolveCatalogItem($input['order_type'], $input['item_key']);
        $settings = PlatformSetting::current();

        $order = GrowthOrder::query()->create([
            'branch_id' => $branch?->id,
            'requested_by' => $user->id,
            'order_type' => $input['order_type'],
            'item_key' => $input['item_key'],
            'item_name' => $catalog['name'],
            'status' => GrowthOrder::STATUS_NEW,
            'priority' => 'normal',
            'message' => $input['message'] ?? null,
            'contact_name' => $input['contact_name'] ?: $user->name,
            'contact_email' => $input['contact_email'] ?: $user->email,
            'contact_phone' => $input['contact_phone'] ?? null,
            'library_name' => $settings->displayName(),
            'library_code' => $settings->library_code,
            'deployment_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            'amount_paise' => isset($catalog['price']) ? ((int) $catalog['price']) * 100 : null,
        ]);

        $ticketSynced = false;
        $ticketError = null;

        $subject = 'Growth order: '.$catalog['name'];
        $body = $this->ticketBody($order, $catalog);

        if (! config('libcontrol.license_server.enabled') && Schema::hasTable('support_tickets')) {
            try {
                $uuid = (string) Str::uuid();
                $syncResult = $this->ticketSync->submit([
                    'uuid' => $uuid,
                    'subject' => $subject,
                    'message' => $body,
                    'category' => 'feature',
                    'priority' => 'normal',
                    'reporter_name' => (string) ($order->contact_name ?: $user->name),
                    'reporter_email' => (string) ($order->contact_email ?: $user->email),
                    'library_code' => $settings->library_code,
                    'library_name' => $settings->displayName(),
                ]);

                $ticket = SupportTicket::query()->create([
                    'uuid' => $uuid,
                    'subject' => $subject,
                    'message' => $body,
                    'category' => 'feature',
                    'status' => SupportTicket::STATUS_OPEN,
                    'priority' => 'normal',
                    'reporter_user_id' => $user->id,
                    'reporter_name' => $order->contact_name ?: $user->name,
                    'reporter_email' => $order->contact_email ?: $user->email,
                    'library_code' => $settings->library_code,
                    'library_name' => $settings->displayName(),
                    'deployment_domain' => $order->deployment_domain,
                    'remote_id' => $syncResult->ok ? $syncResult->remoteId : null,
                    'synced_at' => $syncResult->ok ? now() : null,
                ]);

                $order->support_ticket_id = $ticket->id;
                $order->save();
                $ticketSynced = $syncResult->ok;
                $ticketError = $syncResult->ok ? null : $syncResult->message;
            } catch (\Throwable $e) {
                $ticketError = $e->getMessage();
                $this->storeLocalTicketFallback($order, $user, $subject, $body, $settings);
            }
        } else {
            // Hub / landlord: growth_orders table is the inbox.
            $ticketSynced = true;
        }

        $emailSent = $this->notifyTeam($order, $catalog, $branch);

        return [
            'order' => $order->fresh(),
            'ticket_synced' => $ticketSynced,
            'ticket_error' => $ticketError,
            'email_sent' => $emailSent,
            'whatsapp_url' => $this->whatsappUrl($catalog['name'], $order),
        ];
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    private function notifyTeam(GrowthOrder $order, array $catalog, ?Branch $branch): bool
    {
        $recipients = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('growth.notify_email')),
        ), fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));

        if ($recipients === []) {
            return false;
        }

        dispatch(static function () use ($recipients, $order, $catalog, $branch): void {
            try {
                Mail::to($recipients)->send(new GrowthOrderRequestedMail($order, $catalog, $branch));
            } catch (\Throwable $e) {
                Log::warning('Growth order email failed', ['order' => $order->uuid, 'error' => $e->getMessage()]);
            }
        })->afterResponse();

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveCatalogItem(string $type, string $key): array
    {
        if ($type === 'package') {
            $package = config("growth.packages.{$key}");
            abort_unless(is_array($package), 422, 'Unknown growth package.');

            return $package + ['key' => $key];
        }

        $service = config("growth.services.{$key}");
        abort_unless(is_array($service), 422, 'Unknown growth service.');

        return $service + ['key' => $key];
    }

    public function applyStatusToProfile(GrowthOrder $order): void
    {
        if ($order->order_type !== 'package' || ! $order->branch_id) {
            return;
        }

        $profile = LibraryGrowthProfile::query()->firstOrCreate(
            ['branch_id' => $order->branch_id],
            ['score_cached' => 0]
        );

        if ($order->status === GrowthOrder::STATUS_ACTIVE) {
            $profile->forceFill([
                'active_package' => $order->item_key,
                'package_active_until' => now()->addMonth(),
            ])->save();
        }

        if (in_array($order->status, [GrowthOrder::STATUS_COMPLETED, GrowthOrder::STATUS_CANCELLED, GrowthOrder::STATUS_PAUSED], true)) {
            if ($profile->active_package === $order->item_key) {
                $profile->forceFill([
                    'active_package' => null,
                    'package_active_until' => null,
                ])->save();
            }
        }
    }

    public function whatsappUrl(string $itemName, ?GrowthOrder $order = null): string
    {
        $phone = preg_replace('/\D+/', '', (string) config('growth.whatsapp_phone', '8076105181')) ?: '918076105181';
        if (strlen($phone) === 10) {
            $phone = '91'.$phone;
        }

        $text = 'Hi Phenomit, I want to request LibControl Growth: '.$itemName;
        if ($order) {
            $text .= ' (order '.$order->uuid.')';
        }
        $text .= '. Please share next steps.';

        return 'https://web.whatsapp.com/send?phone='.$phone.'&text='.rawurlencode($text);
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    private function ticketBody(GrowthOrder $order, array $catalog): string
    {
        $lines = [
            'LibControl Growth order request',
            'Type: '.$order->order_type,
            'Item: '.$catalog['name'].' ('.$order->item_key.')',
            'Price: '.($catalog['price_label'] ?? '—'),
            'Library: '.$order->library_name,
            'Branch ID: '.($order->branch_id ?? '—'),
            'Contact: '.$order->contact_name.' <'.$order->contact_email.'>',
            'Phone: '.($order->contact_phone ?: '—'),
            'Order UUID: '.$order->uuid,
            '',
            'Message:',
            $order->message ?: '(none)',
        ];

        return implode("\n", $lines);
    }

    private function storeLocalTicketFallback(
        GrowthOrder $order,
        User $user,
        string $subject,
        string $body,
        PlatformSetting $settings,
    ): void {
        if (! Schema::hasTable('support_tickets')) {
            return;
        }

        $ticket = SupportTicket::query()->create([
            'subject' => $subject,
            'message' => $body,
            'category' => 'feature',
            'status' => SupportTicket::STATUS_OPEN,
            'priority' => 'normal',
            'reporter_user_id' => $user->id,
            'reporter_name' => $order->contact_name ?: $user->name,
            'reporter_email' => $order->contact_email ?: $user->email,
            'library_code' => $settings->library_code,
            'library_name' => $settings->displayName(),
            'deployment_domain' => $order->deployment_domain,
        ]);

        $order->support_ticket_id = $ticket->id;
        $order->save();
    }
}
