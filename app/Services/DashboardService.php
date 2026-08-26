<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\FeePayment;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        private SeatStatusService $seatStatusService,
        private FeeService $feeService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function statsForBranch(?int $branchId): array
    {
        $seatCounts = $this->seatStatusCounts($branchId);

        return [
            ...$seatCounts,
            'total_students' => Student::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
            'total_halls' => Hall::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
            'new_enquiries' => Enquiry::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', 'new')
                ->count(),
        ];
    }

    /**
     * Platform admin overview across all branches.
     *
     * @return array<string, mixed>
     */
    public function adminOverview(?Carbon $from = null, ?Carbon $to = null): array
    {
        $tz = config('libspace.timezone', 'Asia/Kolkata');
        $from ??= Carbon::now($tz)->startOfMonth();
        $to ??= Carbon::now($tz)->endOfDay();
        $prevFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();
        $prevTo = $from->copy()->subMonthNoOverflow()->endOfMonth();
        $today = Carbon::now($tz)->startOfDay();

        $branches = Branch::query()
            ->withCount(['halls', 'students', 'users'])
            ->orderBy('name')
            ->get();

        $allSeats = Seat::query()
            ->with(['bookings.student', 'hall.branch'])
            ->get();

        $seatsByBranch = $allSeats->groupBy(fn (Seat $seat) => (int) ($seat->hall?->branch_id ?? 0));

        $revenueByBranch = FeePayment::query()
            ->selectRaw('halls.branch_id as branch_id, SUM(fee_payments.amount) as total')
            ->join('seat_bookings', 'seat_bookings.id', '=', 'fee_payments.seat_booking_id')
            ->join('seats', 'seats.id', '=', 'seat_bookings.seat_id')
            ->join('halls', 'halls.id', '=', 'seats.hall_id')
            ->whereBetween('fee_payments.payment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('halls.branch_id')
            ->pluck('total', 'branch_id');

        $branchRows = $branches->map(function (Branch $branch) use ($seatsByBranch, $revenueByBranch) {
            $counts = $this->countSeatStatuses($seatsByBranch->get($branch->id, collect()), $branch);
            $seats = $counts['total_seats'];
            $occupied = $counts['occupied'] + $counts['expiring_soon'] + $counts['on_trial'];
            $available = $counts['available'];
            $occupancy = $seats > 0 ? round(($occupied / $seats) * 100, 1) : 0.0;
            $status = $branch->halls_count > 0 ? 'Active' : 'Setup';

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'students' => (int) $branch->students_count,
                'seats' => $seats,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy' => $occupancy,
                'revenue' => round((float) ($revenueByBranch[$branch->id] ?? 0), 2),
                'status' => $status,
                'status_tone' => $status === 'Active' ? 'emerald' : 'amber',
            ];
        })->values();

        $totalSeats = $branchRows->sum('seats');
        $occupied = $branchRows->sum('occupied');
        $available = $branchRows->sum('available');
        $monthRevenue = $branchRows->sum('revenue');
        $prevMonthRevenue = $this->revenueBetween($prevFrom, $prevTo);

        $studentsThisMonth = Student::query()->whereBetween('created_at', [$from, $to])->count();
        $studentsPrevMonth = Student::query()->whereBetween('created_at', [$prevFrom, $prevTo])->count();
        $branchesThisMonth = Branch::query()->whereBetween('created_at', [$from, $to])->count();
        $seatsThisMonth = Seat::query()->whereBetween('created_at', [$from, $to])->count();
        $seatsPrevMonth = Seat::query()->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $feeOverview = $this->feeService->overviewForBranch(null);
        $expiredPlans = $feeOverview['expired'];
        $expiringSoon = $feeOverview['expiring_soon'];

        $expiring1to3 = $expiringSoon->filter(function (SeatBooking $booking) use ($today) {
            if (! $booking->plan_expiry_date) {
                return false;
            }
            $days = $today->diffInDays($booking->plan_expiry_date->copy()->startOfDay(), false);

            return $days >= 0 && $days <= 3;
        });
        $expiring4to7 = $expiringSoon->filter(function (SeatBooking $booking) use ($today) {
            if (! $booking->plan_expiry_date) {
                return false;
            }
            $days = $today->diffInDays($booking->plan_expiry_date->copy()->startOfDay(), false);

            return $days >= 4 && $days <= 7;
        });

        $utilization = $branchRows
            ->filter(fn ($row) => $row['seats'] > 0)
            ->sortByDesc('occupancy')
            ->values();

        $highest = $utilization->first();
        $lowest = $utilization->last();
        $avgOccupancy = $utilization->isNotEmpty()
            ? round($utilization->avg('occupancy'), 1)
            : 0.0;

        $attention = [];
        if ($expiredPlans->isNotEmpty()) {
            $attention[] = [
                'tone' => 'red',
                'title' => $expiredPlans->count().' expired plans',
                'detail' => 'Across '.$expiredPlans->map(fn ($b) => $b->seat?->hall?->branch_id)->filter()->unique()->count().' branches',
                'url' => route('fees.index'),
            ];
        }
        if ($expiringSoon->isNotEmpty()) {
            $attention[] = [
                'tone' => 'amber',
                'title' => $expiringSoon->count().' plans expiring in next 7 days',
                'detail' => 'Across '.$expiringSoon->map(fn ($b) => $b->seat?->hall?->branch_id)->filter()->unique()->count().' branches',
                'url' => route('fees.index'),
            ];
        }
        if ($lowest && $lowest['occupancy'] < 40) {
            $attention[] = [
                'tone' => 'yellow',
                'title' => $lowest['available'].' seats still available',
                'detail' => $lowest['name'].' · '.$lowest['occupancy'].'% occupancy',
                'url' => route('seats.index'),
            ];
        }
        if ($highest && $highest['occupancy'] >= 90) {
            $attention[] = [
                'tone' => 'indigo',
                'title' => $highest['name'].' near full capacity',
                'detail' => $highest['occupancy'].'% occupancy · '.$highest['available'].' seats left',
                'url' => route('seats.index'),
            ];
        }

        $inactiveAdmins = $this->inactiveBranchAdminsCount();
        if ($inactiveAdmins > 0) {
            $attention[] = [
                'tone' => 'blue',
                'title' => $inactiveAdmins.' branch admins inactive',
                'detail' => 'No activity in the last 30 days',
                'url' => route('branch.index'),
            ];
        }

        $systemAlerts = [];
        if ($expiredPlans->isNotEmpty()) {
            $systemAlerts[] = [
                'tone' => 'red',
                'label' => $expiredPlans->count().' expired plans need renewal',
            ];
        }
        if ($expiring1to3->isNotEmpty()) {
            $systemAlerts[] = [
                'tone' => 'amber',
                'label' => $expiring1to3->count().' plans expire within 3 days',
            ];
        }
        if ($available > 0 && $totalSeats > 0 && ($available / $totalSeats) >= 0.5) {
            $systemAlerts[] = [
                'tone' => 'cyan',
                'label' => number_format($available).' seats available system-wide ('.round(($available / $totalSeats) * 100).'% free)',
            ];
        }

        $planExpiryTotal = $expiredPlans->count() + $expiring1to3->count() + $expiring4to7->count();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'from_label' => $from->format('M j'),
            'to_label' => $to->format('M j, Y'),
            'switchUrl' => route('active-branch.switch'),
            'kpis' => [
                'branches' => $branches->count(),
                'branches_delta' => $branchesThisMonth,
                'students' => (int) $branches->sum('students_count'),
                'students_delta_pct' => $this->percentChange($studentsThisMonth, $studentsPrevMonth),
                'seats' => $totalSeats,
                'seats_delta_pct' => $this->percentChange($seatsThisMonth, $seatsPrevMonth),
                'occupied' => $occupied,
                'occupancy_pct' => $totalSeats > 0 ? round(($occupied / $totalSeats) * 100, 2) : 0,
                'available' => $available,
                'availability_pct' => $totalSeats > 0 ? round(($available / $totalSeats) * 100, 2) : 0,
                'monthly_revenue' => $monthRevenue,
                'revenue_delta_pct' => $this->percentChange($monthRevenue, $prevMonthRevenue),
            ],
            'branches' => $branchRows,
            'attention' => $attention,
            'plan_expiry' => [
                'expired' => $expiredPlans->count(),
                'days_1_3' => $expiring1to3->count(),
                'days_4_7' => $expiring4to7->count(),
                'total' => $planExpiryTotal,
            ],
            'utilization' => [
                'rows' => $utilization->take(8)->values(),
                'highest' => $highest,
                'lowest' => $lowest,
                'average' => $avgOccupancy,
            ],
            'system_alerts' => $systemAlerts,
        ];
    }

    /**
     * Branch-scoped dashboard overview.
     *
     * @return array<string, mixed>
     */
    public function branchOverview(?int $branchId): array
    {
        $tz = config('libspace.timezone', 'Asia/Kolkata');
        $todayStart = Carbon::now($tz)->startOfDay();
        $todayEnd = Carbon::now($tz)->endOfDay();
        $yesterdayStart = $todayStart->copy()->subDay();
        $yesterdayEnd = $todayEnd->copy()->subDay();

        $stats = $this->statsForBranch($branchId);
        $feeOverview = $this->feeService->overviewForBranch($branchId);

        $enquiriesToday = Enquiry::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();
        $enquiriesYesterday = Enquiry::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->count();

        $studentsToday = Student::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();
        $studentsYesterday = Student::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->count();

        $revenueToday = $this->revenueBetween($todayStart, $todayEnd, $branchId);
        $revenueYesterday = $this->revenueBetween($yesterdayStart, $yesterdayEnd, $branchId);

        $expiring = $feeOverview['expiring_soon']->take(8)->map(function (SeatBooking $booking) use ($todayStart) {
            $days = $booking->plan_expiry_date
                ? $todayStart->diffInDays($booking->plan_expiry_date->copy()->startOfDay(), false)
                : null;

            return [
                'student_name' => $booking->student?->name,
                'student_code' => $booking->student?->student_code,
                'plan_id' => 'BK-'.$booking->id,
                'expires_on' => $booking->plan_expiry_date?->format('M d, Y'),
                'amount' => (float) $booking->fee_amount,
                'days_left' => $days,
                'days_label' => $days === null
                    ? '—'
                    : ($days <= 0 ? 'Today' : $days.' day'.($days === 1 ? '' : 's').' left'),
            ];
        })->values();

        $active = $feeOverview['active']->take(8)->map(fn (SeatBooking $booking) => [
            'student_name' => $booking->student?->name,
            'student_code' => $booking->student?->student_code,
            'hall_name' => $booking->seat?->hall?->name,
            'seat_number' => $booking->seat?->seat_number,
            'plan_id' => 'BK-'.$booking->id,
            'since' => $booking->joining_date?->format('M d, Y'),
            'status' => 'Active',
        ])->values();

        $recentEnquiries = Enquiry::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Enquiry $enquiry) => [
                'name' => $enquiry->name,
                'initial' => strtoupper(substr((string) $enquiry->name, 0, 1)),
                'message' => $enquiry->message ?: ($enquiry->phone ?: 'Enquiry'),
                'status' => $enquiry->status,
                'status_label' => ucfirst(str_replace('_', ' ', (string) $enquiry->status)),
                'ago' => $enquiry->created_at?->diffForHumans(null, true) ? $enquiry->created_at->diffForHumans() : '',
            ]);

        return [
            'stats' => $stats,
            'today' => [
                'enquiries' => $enquiriesToday,
                'enquiries_delta_pct' => $this->percentChange($enquiriesToday, $enquiriesYesterday),
                'students' => $studentsToday,
                'students_delta_pct' => $this->percentChange($studentsToday, $studentsYesterday),
                'revenue' => $revenueToday,
                'revenue_delta_pct' => $this->percentChange($revenueToday, $revenueYesterday),
                'expiring_plans' => $feeOverview['expiring_soon']->count(),
            ],
            'expiring_plans' => $expiring,
            'active_allocations' => $active,
            'recent_enquiries' => $recentEnquiries,
            'fee_overview' => $feeOverview,
        ];
    }

    /**
     * @return array{total_seats: int, occupied: int, available: int, expiring_soon: int, expired: int, on_trial: int}
     */
    private function seatStatusCounts(?int $branchId): array
    {
        $seats = Seat::query()
            ->with(['bookings.student', 'hall.branch'])
            ->when($branchId, fn ($query) => $query->whereHas('hall', fn ($hallQuery) => $hallQuery->where('branch_id', $branchId)))
            ->get();

        $branch = $branchId ? Branch::query()->find($branchId) : null;

        return $this->countSeatStatuses($seats, $branch);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Seat>  $seats
     * @return array{total_seats: int, occupied: int, available: int, expiring_soon: int, expired: int, on_trial: int}
     */
    private function countSeatStatuses($seats, ?Branch $branch = null): array
    {
        $counts = [
            'total_seats' => $seats->count(),
            'occupied' => 0,
            'available' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'on_trial' => 0,
        ];

        foreach ($seats as $seat) {
            $status = $this->seatStatusService->resolveForSeat($seat, $seat->hall?->branch ?? $branch);

            if ($status === 'occupied' || $status === 'occupied_custom') {
                $counts['occupied']++;
            } elseif ($status === 'available') {
                $counts['available']++;
            } elseif ($status === 'expiring_soon') {
                $counts['expiring_soon']++;
            } elseif ($status === 'expired') {
                $counts['expired']++;
            } elseif ($status === 'on_trial') {
                $counts['on_trial']++;
            }
        }

        return $counts;
    }

    private function revenueBetween(Carbon $from, Carbon $to, ?int $branchId = null): float
    {
        return (float) FeePayment::query()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('booking.seat.hall', fn ($hall) => $hall->where('branch_id', $branchId));
            })
            ->sum('amount');
    }

    private function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }

    private function inactiveBranchAdminsCount(): int
    {
        $cutoff = Carbon::now(config('libspace.timezone', 'Asia/Kolkata'))->subDays(30);

        $recentUserIds = ActivityLog::query()
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::query()
            ->whereNotNull('branch_id')
            ->whereDoesntHave('adminProfile')
            ->whereNotIn('id', $recentUserIds)
            ->where('updated_at', '<', $cutoff)
            ->count();
    }
}
