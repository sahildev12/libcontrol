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
        $from ??= Carbon::now(config('libspace.timezone', 'Asia/Kolkata'))->startOfMonth();
        $to ??= Carbon::now(config('libspace.timezone', 'Asia/Kolkata'))->endOfDay();
        $prevFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();
        $prevTo = $from->copy()->subMonthNoOverflow()->endOfMonth();

        $branches = Branch::query()->orderBy('name')->get();
        $seatCounts = $this->seatStatusCounts(null);
        $totalSeats = $seatCounts['total_seats'];
        $occupied = $seatCounts['occupied'] + $seatCounts['expiring_soon'] + $seatCounts['on_trial'];
        $available = $seatCounts['available'];
        $totalStudents = Student::query()->count();
        $branchCount = $branches->count();

        $monthRevenue = $this->revenueBetween($from, $to);
        $prevMonthRevenue = $this->revenueBetween($prevFrom, $prevTo);

        $studentsThisMonth = Student::query()->whereBetween('created_at', [$from, $to])->count();
        $studentsPrevMonth = Student::query()->whereBetween('created_at', [$prevFrom, $prevTo])->count();
        $branchesThisMonth = Branch::query()->whereBetween('created_at', [$from, $to])->count();
        $seatsThisMonth = Seat::query()->whereBetween('created_at', [$from, $to])->count();
        $seatsPrevMonth = Seat::query()->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $branchRows = $branches->map(function (Branch $branch) use ($from, $to) {
            $counts = $this->seatStatusCounts($branch->id);
            $seats = $counts['total_seats'];
            $occupied = $counts['occupied'] + $counts['expiring_soon'] + $counts['on_trial'];
            $available = $counts['available'];
            $students = Student::query()->where('branch_id', $branch->id)->count();
            $revenue = $this->revenueBetween($from, $to, $branch->id);
            $occupancy = $seats > 0 ? round(($occupied / $seats) * 100, 1) : 0;

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'students' => $students,
                'seats' => $seats,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy' => $occupancy,
                'revenue' => $revenue,
                'status' => 'active',
            ];
        })->values();

        $feeOverview = $this->feeService->overviewForBranch(null);
        $expiredCount = $feeOverview['expired']->count();
        $expiringCount = $feeOverview['expiring_soon']->count();
        $expiredBranches = $feeOverview['expired']
            ->map(fn ($b) => $b->seat?->hall?->branch_id)
            ->filter()
            ->unique()
            ->count();
        $expiringBranches = $feeOverview['expiring_soon']
            ->map(fn ($b) => $b->seat?->hall?->branch_id)
            ->filter()
            ->unique()
            ->count();

        $lowOccupancy = $branchRows
            ->filter(fn ($row) => $row['seats'] > 0 && $row['occupancy'] < 40)
            ->sortBy('occupancy')
            ->take(1)
            ->first();

        $inactiveAdmins = $this->inactiveBranchAdminsCount();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'from_label' => $from->format('M j'),
            'to_label' => $to->format('M j, Y'),
            'kpis' => [
                'branches' => $branchCount,
                'branches_delta' => $branchesThisMonth,
                'students' => $totalStudents,
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
            'revenue_months' => $this->revenueByMonth(6),
            'attention' => [
                'expired_plans' => $expiredCount,
                'expired_branches' => $expiredBranches,
                'expiring_plans' => $expiringCount,
                'expiring_branches' => $expiringBranches,
                'low_occupancy' => $lowOccupancy,
                'inactive_admins' => $inactiveAdmins,
            ],
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

        $counts = [
            'total_seats' => $seats->count(),
            'occupied' => 0,
            'available' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'on_trial' => 0,
        ];

        $branch = $branchId ? Branch::query()->find($branchId) : null;

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

    /**
     * @return list<array{label: string, amount: float}>
     */
    private function revenueByMonth(int $months = 6): array
    {
        $tz = config('libspace.timezone', 'Asia/Kolkata');
        $cursor = Carbon::now($tz)->startOfMonth()->subMonthsNoOverflow($months - 1);
        $rows = [];

        for ($i = 0; $i < $months; $i++) {
            $start = $cursor->copy()->startOfMonth();
            $end = $cursor->copy()->endOfMonth();
            $rows[] = [
                'label' => $start->format("M 'y"),
                'amount' => $this->revenueBetween($start, $end),
            ];
            $cursor->addMonthNoOverflow();
        }

        return $rows;
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
