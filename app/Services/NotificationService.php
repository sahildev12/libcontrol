<?php

namespace App\Services;

use App\Models\Enquiry;
use App\Models\SeatBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NotificationService
{
    public function alertsForBranch(int $branchId, ?int $limit = null): Collection
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);
        $alerts = collect();
        $counter = 1;

        $expiringBookings = SeatBooking::query()
            ->with(['student:id,student_code,name', 'seat.hall:id,name'])
            ->whereHas('seat.hall', fn ($query) => $query->where('branch_id', $branchId))
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->whereDate('plan_expiry_date', '>=', $today)
            ->whereDate('plan_expiry_date', '<=', $soon)
            ->orderBy('plan_expiry_date')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get();

        foreach ($expiringBookings as $booking) {
            $alerts->push([
                'id' => $counter++,
                'type' => 'fee_expiring',
                'title' => 'Plan expiring soon',
                'message' => "{$booking->student?->name} ({$booking->student?->student_code}) plan expires on {$booking->plan_expiry_date->format('M d, Y')}.",
                'date' => $booking->plan_expiry_date?->format('M d, Y'),
            ]);
        }

        $expiredBookings = SeatBooking::query()
            ->with(['student:id,student_code,name'])
            ->whereHas('seat.hall', fn ($query) => $query->where('branch_id', $branchId))
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->whereDate('plan_expiry_date', '<', $today)
            ->orderByDesc('plan_expiry_date')
            ->when($limit, fn ($query) => $query->limit(max(1, ($limit ?? 10) - $alerts->count())))
            ->get();

        foreach ($expiredBookings as $booking) {
            $alerts->push([
                'id' => $counter++,
                'type' => 'fee_expired',
                'title' => 'Plan expired',
                'message' => "{$booking->student?->name} ({$booking->student?->student_code}) plan expired on {$booking->plan_expiry_date->format('M d, Y')}.",
                'date' => $booking->plan_expiry_date?->format('M d, Y'),
            ]);
        }

        $remaining = $limit ? max(0, $limit - $alerts->count()) : null;

        $newEnquiries = Enquiry::query()
            ->where('branch_id', $branchId)
            ->where('status', 'new')
            ->orderByDesc('created_at')
            ->when($remaining, fn ($query) => $query->limit($remaining))
            ->get();

        foreach ($newEnquiries as $enquiry) {
            $alerts->push([
                'id' => $counter++,
                'type' => 'new_enquiry',
                'title' => 'New enquiry',
                'message' => "{$enquiry->name} submitted an enquiry ({$enquiry->phone}).",
                'date' => $enquiry->created_at?->format('M d, Y'),
            ]);
        }

        return $alerts->values();
    }
}
