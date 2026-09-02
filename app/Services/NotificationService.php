<?php

namespace App\Services;

use App\Models\Enquiry;
use App\Models\NotificationRead;
use App\Models\SeatBooking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

        if (config('libspace.modules.enquiries')) {
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

        foreach (array_unique(array_filter($keys)) as $key) {
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
}
