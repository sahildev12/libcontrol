<?php

namespace App\Services\Growth;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\User;
use App\Services\FeeService;
use App\Services\ProfitLossService;
use App\Services\Profile\LibraryProfileCompletionService;
use App\Services\SeatStatusService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class LibraryBusinessPerformanceService
{
    public function __construct(
        private LibraryProfileCompletionService $profileCompletion,
        private FeeService $fees,
        private ProfitLossService $profitLoss,
        private SeatStatusService $seatStatus,
    ) {}

    /**
     * @return array{
     *     score: float,
     *     score_rounded: int,
     *     max: int,
     *     data_confidence: int,
     *     factors: list<array{
     *         key: string,
     *         label: string,
     *         weight: float,
     *         score: float|null,
     *         available: bool,
     *         detail: string,
     *         contribution: float
     *     }>,
     *     highlights: array{occupancy: float|null, renewals: float|null, profile: int}
     * }
     */
    public function scoreForLibrary(?User $user = null, ?int $branchId = null): array
    {
        $weights = config('growth.business_performance.weights', []);
        $factors = [
            $this->factorProfile($weights['profile_completion'] ?? 0.10, $user),
            $this->factorOccupancy($weights['occupancy'] ?? 0.20, $branchId),
            $this->factorRenewals($weights['renewals'] ?? 0.15, $branchId),
            $this->factorBranchStaff($weights['branch_staff'] ?? 0.10, $branchId),
            $this->factorMembershipPayment($weights['membership_payment'] ?? 0.15, $branchId),
            $this->factorExpensesFinance($weights['expenses_finance'] ?? 0.10, $branchId),
            $this->factorOperations($weights['operations'] ?? 0.15, $branchId),
            $this->factorSystemUsage($weights['system_usage'] ?? 0.05, $branchId),
        ];

        $availableWeight = collect($factors)->where('available', true)->sum('weight');
        $dataConfidence = (int) round(min(100, max(0, $availableWeight * 100)));

        $score = 0.0;
        if ($availableWeight > 0) {
            foreach ($factors as &$factor) {
                if (! $factor['available'] || $factor['score'] === null) {
                    $factor['contribution'] = 0.0;

                    continue;
                }

                // Renormalize when some factors lack data (missing ≠ zero).
                $effectiveWeight = $factor['weight'] / $availableWeight;
                $factor['contribution'] = round($factor['score'] * $effectiveWeight, 2);
                $score += $factor['contribution'];
            }
            unset($factor);
        }

        $score = round(min(100, max(0, $score)), 1);

        $byKey = collect($factors)->keyBy('key');

        return [
            'score' => $score,
            'score_rounded' => (int) round($score),
            'max' => 100,
            'data_confidence' => $dataConfidence,
            'factors' => array_values($factors),
            'highlights' => [
                'occupancy' => $byKey->get('occupancy')['score'] ?? null,
                'renewals' => $byKey->get('renewals')['score'] ?? null,
                'profile' => (int) round($byKey->get('profile_completion')['score'] ?? 0),
            ],
        ];
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorProfile(float $weight, ?User $user): array
    {
        $pct = (int) ($this->profileCompletion->scoreForLibrary($user)['pct'] ?? 0);

        return $this->factor(
            'profile_completion',
            'Profile Completion',
            $weight,
            (float) $pct,
            true,
            $pct.'% of business setup complete.'
        );
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorOccupancy(float $weight, ?int $branchId): array
    {
        if (! Schema::hasTable('seats')) {
            return $this->insufficient('occupancy', 'Occupancy & Seat Utilization', $weight, 'Seat data not available yet.');
        }

        $counts = $this->seatCounts($branchId);
        $total = $counts['total_seats'];

        if ($total < 1) {
            return $this->insufficient('occupancy', 'Occupancy & Seat Utilization', $weight, 'Add seats to measure occupancy.');
        }

        $occupied = $counts['occupied'] + $counts['expiring_soon'] + $counts['on_trial'];
        $pct = round(($occupied / $total) * 100, 1);

        return $this->factor(
            'occupancy',
            'Occupancy & Seat Utilization',
            $weight,
            $pct,
            true,
            "{$occupied}/{$total} seats occupied ({$pct}%)."
        );
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorRenewals(float $weight, ?int $branchId): array
    {
        if (! Schema::hasTable('seat_bookings')) {
            return $this->insufficient('renewals', 'Renewals & Retention', $weight, 'Membership data not available yet.');
        }

        $overview = $this->fees->overviewForBranch($branchId);
        $active = $overview['active']->count();
        $expiring = $overview['expiring_soon']->count();
        $expired = $overview['expired']->count();
        $known = $active + $expiring + $expired;

        if ($known < 1) {
            return $this->insufficient('renewals', 'Renewals & Retention', $weight, 'No membership plans to measure retention yet.');
        }

        // Still-active (incl. expiring soon) vs lapsed.
        $retained = $active + $expiring;
        $pct = round(($retained / $known) * 100, 1);

        return $this->factor(
            'renewals',
            'Renewals & Retention',
            $weight,
            $pct,
            true,
            "{$retained} active / {$known} plans ({$pct}% retained)."
        );
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorBranchStaff(float $weight, ?int $branchId): array
    {
        if (! Schema::hasTable('branches')) {
            return $this->insufficient('branch_staff', 'Branch & Staff', $weight, 'Branch data not available yet.');
        }

        $branches = Branch::query()
            ->when($branchId, fn ($q) => $q->where('id', $branchId))
            ->get();

        if ($branches->isEmpty()) {
            return $this->insufficient('branch_staff', 'Branch & Staff', $weight, 'Create a branch to score setup.');
        }

        // Single-branch libraries are not penalized — score setup quality, not branch count.
        $points = 0;
        $max = 100;

        $contactOk = $branches->contains(function (Branch $b) {
            return filled($b->phone) || filled($b->address);
        });
        if ($contactOk) {
            $points += 40;
        }

        $hoursOk = $branches->contains(function (Branch $b) {
            return (bool) $b->is_open_24_hours
                || (filled($b->library_open_time) && filled($b->library_close_time));
        });
        if ($hoursOk) {
            $points += 20;
        }

        $hasOperator = Admin::query()->where('admin_type', Admin::TYPE_CLIENT)->exists()
            || User::query()
                ->whereNotNull('branch_id')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->exists();
        if ($hasOperator) {
            $points += 20;
        }

        $recentActivity = Schema::hasTable('activity_logs')
            && ActivityLog::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();
        if ($recentActivity) {
            $points += 20;
        }

        $detail = $branches->count() === 1
            ? 'Single-branch setup scored on contact, hours, operators, and recent activity.'
            : $branches->count().' branches scored on contact, hours, operators, and recent activity.';

        return $this->factor('branch_staff', 'Branch & Staff', $weight, (float) $points, true, $detail);
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorMembershipPayment(float $weight, ?int $branchId): array
    {
        if (! Schema::hasTable('seat_bookings')) {
            return $this->insufficient('membership_payment', 'Membership & Payment', $weight, 'Fee data not available yet.');
        }

        $bookings = SeatBooking::query()
            ->with(['installments', 'payments'])
            ->when($branchId, fn ($q) => $q->whereHas('seat.hall', fn ($h) => $h->where('branch_id', $branchId)))
            ->where('fee_amount', '>', 0)
            ->whereNull('cancelled_at')
            ->get()
            ->filter(fn (SeatBooking $b) => $this->fees->hasFeeSetup($b));

        if ($bookings->isEmpty()) {
            return $this->insufficient('membership_payment', 'Membership & Payment', $weight, 'No active fee plans yet.');
        }

        $totalFee = 0.0;
        $totalPaid = 0.0;
        $overdueCount = 0;

        foreach ($bookings as $booking) {
            $fee = round((float) $booking->fee_amount, 2);
            $paid = $this->fees->paidAmount($booking);
            $totalFee += $fee;
            $totalPaid += min($paid, $fee);
            if ($this->fees->paymentStatus($booking) === 'overdue') {
                $overdueCount++;
            }
        }

        $collection = $totalFee > 0
            ? min(100, round(($totalPaid / $totalFee) * 100, 1))
            : 100.0;
        $penalty = min(40.0, round(($overdueCount / max($bookings->count(), 1)) * 40, 1));
        $score = max(0.0, round($collection - $penalty, 1));

        return $this->factor(
            'membership_payment',
            'Membership & Payment',
            $weight,
            $score,
            true,
            "Collection {$collection}%".($overdueCount > 0 ? ", {$overdueCount} overdue" : '').'.'
        );
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorExpensesFinance(float $weight, ?int $branchId): array
    {
        if (! Schema::hasTable('fee_payments') && ! Schema::hasTable('expenses')) {
            return $this->insufficient('expenses_finance', 'Expenses & Finance', $weight, 'Finance data not available yet.');
        }

        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $from = Carbon::now($tz)->startOfMonth();
        $to = Carbon::now($tz)->endOfDay();
        $pl = $this->profitLoss->summary($branchId, $from, $to);

        $income = (float) ($pl['fee_income_total'] ?? 0);
        $expenses = (float) ($pl['total_expenses'] ?? 0);

        if ($income <= 0 && $expenses <= 0) {
            return $this->insufficient('expenses_finance', 'Expenses & Finance', $weight, 'No fee income or expenses recorded this month.');
        }

        if ($income <= 0) {
            // Expenses tracked but no income yet — acknowledge tracking without calling it healthy.
            return $this->factor(
                'expenses_finance',
                'Expenses & Finance',
                $weight,
                25.0,
                true,
                'Expenses recorded this month, but no fee income yet.'
            );
        }

        // Break-even ≈ 50. Positive estimated operating surplus raises the score.
        $marginPct = (($income - $expenses) / $income) * 100;
        $score = max(0.0, min(100.0, round($marginPct + 50, 1)));
        $surplus = round($income - $expenses, 2);

        return $this->factor(
            'expenses_finance',
            'Expenses & Finance',
            $weight,
            $score,
            true,
            'Estimated operating surplus ₹'.number_format($surplus, 0).' this month.'
        );
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorOperations(float $weight, ?int $branchId): array
    {
        $points = 0;
        $parts = [];

        $hallCount = Schema::hasTable('halls')
            ? Hall::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count()
            : 0;
        if ($hallCount > 0) {
            $points += 25;
            $parts[] = $hallCount.' hall(s)';
        }

        $seatCount = Schema::hasTable('seats')
            ? Seat::query()
                ->when($branchId, fn ($q) => $q->whereHas('hall', fn ($h) => $h->where('branch_id', $branchId)))
                ->count()
            : 0;
        if ($seatCount > 0) {
            $points += 25;
            $parts[] = $seatCount.' seat(s)';
        }

        $studentCount = Schema::hasTable('students')
            ? Student::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count()
            : 0;
        if ($studentCount > 0) {
            $points += 25;
            $parts[] = $studentCount.' student(s)';
        }

        $opsFlow = false;
        if (Schema::hasTable('enquiries') && Enquiry::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->exists()) {
            $opsFlow = true;
        }
        if (! $opsFlow && Schema::hasTable('seat_bookings')) {
            $opsFlow = SeatBooking::query()
                ->when($branchId, fn ($q) => $q->whereHas('seat.hall', fn ($h) => $h->where('branch_id', $branchId)))
                ->where(function ($q) {
                    $q->where('status', 'on_trial')
                        ->orWhereNotNull('trial_start')
                        ->orWhere('fee_amount', '>', 0);
                })
                ->exists();
        }
        if ($opsFlow) {
            $points += 25;
            $parts[] = 'trials/fees/enquiries in use';
        }

        if ($points < 25) {
            return $this->insufficient('operations', 'Operations', $weight, 'Set up halls, seats, and students to score operations.');
        }

        return $this->factor(
            'operations',
            'Operations',
            $weight,
            (float) $points,
            true,
            $parts === [] ? 'Operational setup in progress.' : implode(', ', $parts).'.'
        );
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factorSystemUsage(float $weight, ?int $branchId): array
    {
        $checks = 0;
        $passed = 0;

        // 1) Payments maintained when plans exist
        $checks++;
        $activePlans = 0;
        if (Schema::hasTable('seat_bookings')) {
            $activePlans = (int) ($this->fees->insightSummary($branchId)['active_plans'] ?? 0);
        }
        if ($activePlans < 1) {
            $passed++;
        } elseif (Schema::hasTable('fee_payments')) {
            $hasRecentPayment = \App\Models\FeePayment::query()
                ->when($branchId, fn ($q) => $q->whereHas('booking.seat.hall', fn ($h) => $h->where('branch_id', $branchId)))
                ->where('payment_date', '>=', now()->subDays(45)->toDateString())
                ->exists();
            if ($hasRecentPayment) {
                $passed++;
            }
        }

        // 2) Student contact data quality
        $checks++;
        if (Schema::hasTable('students')) {
            $students = Student::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
            $total = (clone $students)->count();
            if ($total < 1) {
                $passed++;
            } else {
                $withPhone = (clone $students)->whereNotNull('phone')->where('phone', '!=', '')->count();
                if (($withPhone / $total) >= 0.7) {
                    $passed++;
                }
            }
        } else {
            $passed++;
        }

        // 3) Meaningful activity recently (not login spam)
        $checks++;
        if (Schema::hasTable('activity_logs')) {
            $coreActions = ActivityLog::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('created_at', '>=', now()->subDays(14))
                ->where(function ($q) {
                    $q->where('action', 'like', 'fees.%')
                        ->orWhere('action', 'like', 'students.%')
                        ->orWhere('action', 'like', 'seats.%')
                        ->orWhere('action', 'like', 'enquiries.%')
                        ->orWhere('action', 'like', 'bookings.%');
                })
                ->exists();
            if ($coreActions) {
                $passed++;
            }
        }

        // 4) Enquiry pipeline maintained (if used)
        $checks++;
        if (Schema::hasTable('enquiries') && config('libcontrol.modules.enquiries')) {
            $totalEnquiries = Enquiry::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count();
            if ($totalEnquiries < 1) {
                $passed++;
            } else {
                $stuck = Enquiry::query()
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->where('status', 'new')
                    ->where('created_at', '<', now()->subDays(14))
                    ->count();
                if ($stuck / $totalEnquiries <= 0.5) {
                    $passed++;
                }
            }
        } else {
            $passed++;
        }

        $score = round(($passed / max($checks, 1)) * 100, 1);

        return $this->factor(
            'system_usage',
            'System Usage & Activity',
            $weight,
            $score,
            true,
            "Data quality checks passed: {$passed}/{$checks}."
        );
    }

    /**
     * @return array{total_seats: int, occupied: int, available: int, expiring_soon: int, expired: int, on_trial: int}
     */
    private function seatCounts(?int $branchId): array
    {
        $seats = Seat::query()
            ->with(['bookings.student', 'hall.branch'])
            ->when($branchId, fn ($q) => $q->whereHas('hall', fn ($h) => $h->where('branch_id', $branchId)))
            ->get();

        $branch = $branchId ? Branch::query()->find($branchId) : null;
        $counts = [
            'total_seats' => $seats->count(),
            'occupied' => 0,
            'available' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'on_trial' => 0,
        ];

        foreach ($seats as $seat) {
            $status = $this->seatStatus->resolveForSeat($seat, $seat->hall?->branch ?? $branch);
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

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function factor(string $key, string $label, float $weight, float $score, bool $available, string $detail): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'weight' => $weight,
            'score' => round(min(100, max(0, $score)), 1),
            'available' => $available,
            'detail' => $detail,
            'contribution' => 0.0,
        ];
    }

    /**
     * @return array{key: string, label: string, weight: float, score: float|null, available: bool, detail: string, contribution: float}
     */
    private function insufficient(string $key, string $label, float $weight, string $detail): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'weight' => $weight,
            'score' => null,
            'available' => false,
            'detail' => $detail,
            'contribution' => 0.0,
        ];
    }
}
