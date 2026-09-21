<?php

namespace App\Services;

use App\Models\Enquiry;
use App\Models\NotificationRead;
use App\Models\SeatBooking;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public function alertsForBranch(?int $branchId, ?User $user = null, ?int $limit = null): Collection
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);
        $alerts = collect();
        $readKeys = [];
        $dismissedKeys = [];

        if ($user) {
            $states = NotificationRead::query()->where('user_id', $user->id)->get(['alert_key', 'read_at', 'dismissed_at']);
            $readKeys = $states->whereNotNull('read_at')->pluck('alert_key')->all();
            $dismissedKeys = $states->whereNotNull('dismissed_at')->pluck('alert_key')->all();
        }

        $expiringBookings = SeatBooking::query()
            ->with(['student:id,student_code,name,phone', 'seat.hall:id,name'])
            ->when($branchId, fn ($query) => $query->whereHas('seat.hall', fn ($hallQuery) => $hallQuery->where('branch_id', $branchId)))
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->whereDate('plan_expiry_date', '>=', $today)
            ->whereDate('plan_expiry_date', '<=', $soon)
            ->orderBy('plan_expiry_date')
            ->get();

        foreach ($expiringBookings as $booking) {
            $name = $booking->student?->name ?: 'A student';
            $date = $booking->plan_expiry_date?->format('M d, Y');
            $key = 'fee_expiring:'.$booking->id;

        $alerts->push($this->formatAlert(
            $key,
            'fee_expiring',
            'Plan ending soon',
            "{$name}'s plan ends on {$date}.",
            $date,
            $booking->plan_expiry_date,
            route('fees.index'),
            [
                ['label' => 'Student', 'value' => $name],
                ['label' => 'Plan ends on', 'value' => $date],
                ['label' => 'Hall', 'value' => $booking->seat?->hall?->name ?: '—'],
                ['label' => 'Phone', 'value' => $booking->student?->phone ?: '—'],
            ],
            $readKeys,
            'Open fees',
        ));
        }

        $expiredBookings = SeatBooking::query()
            ->with(['student:id,student_code,name,phone', 'seat.hall:id,name'])
            ->when($branchId, fn ($query) => $query->whereHas('seat.hall', fn ($hallQuery) => $hallQuery->where('branch_id', $branchId)))
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->whereDate('plan_expiry_date', '<', $today)
            ->orderByDesc('plan_expiry_date')
            ->get();

        foreach ($expiredBookings as $booking) {
            $name = $booking->student?->name ?: 'A student';
            $date = $booking->plan_expiry_date?->format('M d, Y');
            $key = 'fee_expired:'.$booking->id;

            $alerts->push($this->formatAlert(
            $key,
            'fee_expired',
            'Plan ended',
            "{$name}'s plan ended on {$date}.",
            $date,
            $booking->plan_expiry_date,
            route('fees.index'),
            [
                ['label' => 'Student', 'value' => $name],
                ['label' => 'Plan ended on', 'value' => $date],
                ['label' => 'Hall', 'value' => $booking->seat?->hall?->name ?: '—'],
                ['label' => 'Phone', 'value' => $booking->student?->phone ?: '—'],
            ],
            $readKeys,
            'Open fees',
        ));
        }

        if ($user && $this->shouldIncludeSupportTickets($user)) {
            foreach ($this->unreadSupportTickets()->get() as $ticket) {
                $alerts->push($this->formatSupportTicketAlert($ticket));
            }
        }

        if ($user && $this->shouldIncludeClientSupportTicketUpdates($user)) {
            foreach ($this->pendingClientSupportTickets($user)->get() as $ticket) {
                $alerts->push($this->formatClientSupportTicketAlert($ticket));
            }
        }

        if (config('libcontrol.modules.enquiries')) {
            $newEnquiries = Enquiry::query()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->where('status', 'new')
                ->orderByDesc('created_at')
                ->get();

            foreach ($newEnquiries as $enquiry) {
                $key = 'new_enquiry:'.$enquiry->id;
                $date = $enquiry->created_at?->format('M d, Y');

                $alerts->push($this->formatAlert(
                    $key,
                    'new_enquiry',
                    'New enquiry',
                    "{$enquiry->name} asked about joining.",
                    $date,
                    $enquiry->created_at,
                    route('enquiries.index'),
                    [
                        ['label' => 'Name', 'value' => $enquiry->name],
                        ['label' => 'Phone', 'value' => $enquiry->phone ?: '—'],
                        ['label' => 'Email', 'value' => $enquiry->email ?: '—'],
                        ['label' => 'Received', 'value' => $date],
                    ],
                    $readKeys,
                    'Open enquiries',
                ));
            }
        }

        $alerts = $alerts
            ->reject(fn (array $alert) => in_array($alert['id'], $dismissedKeys, true))
            ->sortByDesc(fn (array $alert) => $alert['sort_at'])
            ->values();

        return $limit ? $alerts->take($limit)->values() : $alerts;
    }

    public function unreadCount(?int $branchId, User $user): int
    {
        return $this->alertsForBranch($branchId, $user)->where('unread', true)->count();
    }

    /**
     * @param  list<string>  $keys
     */
    public function markKeysRead(User $user, array $keys): void
    {
        $now = now();
        $notificationKeys = [];

        foreach (array_unique(array_filter($keys)) as $key) {
            if (str_starts_with($key, 'support_ticket:')) {
                $ticketId = (int) substr($key, strlen('support_ticket:'));

                if ($ticketId > 0) {
                    SupportTicket::query()
                        ->whereKey($ticketId)
                        ->whereNull('read_at')
                        ->update(['read_at' => $now]);
                }

                continue;
            }

            if (str_starts_with($key, 'support_ticket_update:')) {
                $ticketId = (int) substr($key, strlen('support_ticket_update:'));

                if ($ticketId > 0) {
                    SupportTicket::query()
                        ->whereKey($ticketId)
                        ->update(['client_update_pending' => false]);
                }

                continue;
            }

            $notificationKeys[] = $key;
        }

        foreach ($notificationKeys as $key) {
            NotificationRead::query()->updateOrCreate(
                ['user_id' => $user->id, 'alert_key' => $key],
                ['read_at' => $now],
            );
        }
    }

    public function markAllRead(?int $branchId, User $user): void
    {
        $keys = $this->alertsForBranch($branchId, $user)->pluck('id')->all();
        $this->markKeysRead($user, $keys);

        if ($this->shouldIncludeSupportTickets($user)) {
            $this->unreadSupportTickets()->update(['read_at' => now()]);
        }

        if ($this->shouldIncludeClientSupportTicketUpdates($user)) {
            $this->pendingClientSupportTickets($user)->update(['client_update_pending' => false]);
        }
    }

    public function shouldIncludeSupportTickets(User $user): bool
    {
        return $user->isDeveloperAdmin() && (bool) config('libcontrol.license_server.enabled');
    }

    public function clientSupportTicketUpdateCount(User $user): int
    {
        if (! $this->shouldIncludeClientSupportTicketUpdates($user)) {
            return 0;
        }

        return $this->pendingClientSupportTickets($user)->count();
    }

    public function supportTicketUnreadCount(User $user): int
    {
        if (! $this->shouldIncludeSupportTickets($user)) {
            return 0;
        }

        return $this->unreadSupportTickets()->count();
    }

    /**
     * @param  list<string>  $keys
     */
    public function dismissKeys(User $user, array $keys): void
    {
        $now = now();

        foreach (array_unique(array_filter($keys)) as $key) {
            NotificationRead::query()->updateOrCreate(
                ['user_id' => $user->id, 'alert_key' => $key],
                ['read_at' => $now, 'dismissed_at' => $now],
            );
        }
    }

    /**
     * @param  list<array{label: string, value: string|null}>  $details
     * @param  list<string>  $readKeys
     * @return array<string, mixed>
     */
    private function formatAlert(
        string $key,
        string $type,
        string $title,
        string $message,
        ?string $date,
        mixed $sortAt,
        string $url,
        array $details,
        array $readKeys,
        string $actionLabel,
    ): array {
        $typeLabel = match ($type) {
            'fee_expiring' => 'Plan ending soon',
            'fee_expired' => 'Plan ended',
            'new_enquiry' => 'New enquiry',
            'support_ticket' => 'Support ticket',
            'support_ticket_update' => 'Ticket updated',
            default => 'Alert',
        };

        return [
            'id' => $key,
            'type' => $type,
            'type_label' => $typeLabel,
            'title' => $title,
            'message' => $message,
            'date' => $date,
            'sort_at' => $sortAt instanceof \DateTimeInterface ? $sortAt->getTimestamp() : 0,
            'url' => $url,
            'details' => $details,
            'action_label' => $actionLabel,
            'unread' => ! in_array($key, $readKeys, true),
        ];
    }

    public function shouldIncludeClientSupportTicketUpdates(User $user): bool
    {
        return ! config('libcontrol.license_server.enabled')
            && Schema::hasTable('support_tickets')
            && Schema::hasColumn('support_tickets', 'client_update_pending');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<SupportTicket>
     */
    private function pendingClientSupportTickets(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return SupportTicket::query()
            ->where('client_update_pending', true)
            ->when(! $user->isAnyAdmin(), fn ($query) => $query->where('reporter_user_id', $user->id))
            ->orderByDesc('updated_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<SupportTicket>
     */
    private function unreadSupportTickets(): \Illuminate\Database\Eloquent\Builder
    {
        return SupportTicket::query()
            ->whereNull('read_at')
            ->orderByDesc('created_at');
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private function formatClientSupportTicketAlert(SupportTicket $ticket): array
    {
        $date = $ticket->updated_at?->format('M d, Y');
        $message = $ticket->admin_notes
            ? "Status is now {$ticket->statusLabel()}. Phenomit left a reply."
            : "Status is now {$ticket->statusLabel()}.";

        return [
            'id' => 'support_ticket_update:'.$ticket->id,
            'type' => 'support_ticket_update',
            'type_label' => 'Ticket updated',
            'title' => 'Support ticket updated',
            'message' => "{$ticket->subject} — {$message}",
            'date' => $date,
            'sort_at' => $ticket->updated_at?->getTimestamp() ?? 0,
            'url' => route('help-support.index'),
            'details' => [
                ['label' => 'Subject', 'value' => $ticket->subject],
                ['label' => 'Status', 'value' => $ticket->statusLabel()],
                ['label' => 'Reply', 'value' => $ticket->admin_notes ?: '—'],
                ['label' => 'Updated', 'value' => $date],
            ],
            'action_label' => 'Open Help & Support',
            'unread' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSupportTicketAlert(SupportTicket $ticket): array
    {
        $library = $ticket->library_name ?: $ticket->deployment_domain ?: 'Client library';
        $date = $ticket->created_at?->format('M d, Y');

        return [
            'id' => 'support_ticket:'.$ticket->id,
            'type' => 'support_ticket',
            'type_label' => 'Support ticket',
            'title' => 'New support ticket',
            'message' => "{$library} — {$ticket->subject}",
            'date' => $date,
            'sort_at' => $ticket->created_at?->getTimestamp() ?? 0,
            'url' => route('developer.support-tickets.show', $ticket),
            'details' => [
                ['label' => 'Library', 'value' => $library],
                ['label' => 'Subject', 'value' => $ticket->subject],
                ['label' => 'Reporter', 'value' => $ticket->reporter_name ?: '—'],
                ['label' => 'Received', 'value' => $date],
            ],
            'action_label' => 'Open ticket',
            'unread' => true,
        ];
    }
}
