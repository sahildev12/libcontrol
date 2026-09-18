<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\SeatBooking;
use Illuminate\Support\Carbon;

class ProfitLossService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?int $branchId, Carbon $from, Carbon $to): array
    {
        $periodDays = max(1, $from->diffInDays($to) + 1);
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($periodDays - 1)->startOfDay();

        $current = $this->periodMetrics($branchId, $from, $to);
        $previous = $this->periodMetrics($branchId, $prevFrom, $prevTo);
        $feeCurrent = $this->feeIncomeMetrics($branchId, $from, $to);
        $feePrevious = $this->feeIncomeMetrics($branchId, $prevFrom, $prevTo);

        $totalExpenses = $current['total_expenses'];
        $totalFeeIncome = $feeCurrent['total'];
        $netProfit = round($totalFeeIncome - $totalExpenses, 2);
        $previousNet = round($feePrevious['total'] - $previous['total_expenses'], 2);
        $topCategory = $current['top_category'];

        return [
            'fee_income_total' => $totalFeeIncome,
            'fee_income_delta_pct' => $this->deltaPercent($totalFeeIncome, $feePrevious['total']),
            'fee_payment_count' => $feeCurrent['count'],
            'fee_payment_count_delta_pct' => $this->deltaPercent((float) $feeCurrent['count'], (float) $feePrevious['count']),
            'total_expenses' => $totalExpenses,
            'total_expenses_delta_pct' => $this->deltaPercent($totalExpenses, $previous['total_expenses']),
            'net_profit' => $netProfit,
            'net_profit_delta_pct' => $this->deltaPercent($netProfit, $previousNet),
            'expense_count' => $current['expense_count'],
            'expense_count_delta_pct' => $this->deltaPercent((float) $current['expense_count'], (float) $previous['expense_count']),
            'cash_total' => $current['cash_total'],
            'cash_count' => $current['cash_count'],
            'cash_delta_pct' => $this->deltaPercent($current['cash_total'], $previous['cash_total']),
            'bank_transfer_total' => $current['bank_transfer_total'],
            'bank_transfer_count' => $current['bank_transfer_count'],
            'bank_transfer_delta_pct' => $this->deltaPercent($current['bank_transfer_total'], $previous['bank_transfer_total']),
            'top_category' => $topCategory['label'],
            'top_category_amount' => $topCategory['amount'],
            'top_category_share_pct' => $topCategory['share_pct'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function ledger(?int $branchId, Carbon $from, Carbon $to): array
    {
        $feeEntries = $this->feePaymentQuery($branchId)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(function (FeePayment $payment) {
                $student = $payment->booking?->student;
                $hall = $payment->booking?->seat?->hall;

                return [
                    'id' => 'fee-'.$payment->id,
                    'entry_type' => 'income',
                    'entry_label' => 'Fee received',
                    'date' => $payment->payment_date?->format('d M Y'),
                    'date_iso' => $payment->payment_date?->toDateString(),
                    'title' => trim(($student?->student_code ? $student->student_code.' — ' : '').($student?->name ?? 'Student fee')),
                    'subtitle' => collect([
                        $hall?->name,
                        $payment->booking?->seat?->seat_number ? 'Seat '.$payment->booking->seat->seat_number : null,
                        $payment->reference,
                    ])->filter()->implode(' • '),
                    'amount' => round((float) $payment->amount, 2),
                    'signed_amount' => round((float) $payment->amount, 2),
                    'payment_method' => (string) ($payment->payment_method ?? 'cash'),
                    'payment_method_label' => ucfirst(str_replace('_', ' ', (string) ($payment->payment_method ?? 'cash'))),
                    'branch_name' => $hall?->branch?->name,
                    'recorded_by_name' => $payment->receivedBy?->name,
                ];
            });

        $expenseEntries = $this->expenseQuery($branchId)
            ->with(['branch:id,name', 'recorder:id,name'])
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(function (Expense $expense) {
                $labels = config('libcontrol.expense_categories', []);

                return [
                    'id' => 'expense-'.$expense->id,
                    'entry_type' => 'expense',
                    'entry_label' => (string) ($labels[$expense->category] ?? $expense->category),
                    'date' => $expense->expense_date?->format('d M Y'),
                    'date_iso' => $expense->expense_date?->toDateString(),
                    'title' => $expense->title,
                    'subtitle' => $expense->notes,
                    'amount' => round((float) $expense->amount, 2),
                    'signed_amount' => round((float) $expense->amount * -1, 2),
                    'payment_method' => (string) $expense->payment_method,
                    'payment_method_label' => str_replace('_', ' ', (string) $expense->payment_method),
                    'branch_name' => $expense->branch?->name,
                    'recorded_by_name' => $expense->recorder?->name,
                ];
            });

        return $feeEntries
            ->concat($expenseEntries)
            ->sortByDesc(fn (array $entry) => $entry['date_iso'] ?? '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeExpense(Expense $expense): array
    {
        $labels = config('libcontrol.expense_categories', []);

        return [
            'id' => $expense->id,
            'branch_id' => $expense->branch_id,
            'branch_name' => $expense->branch?->name,
            'category' => $expense->category,
            'category_label' => (string) ($labels[$expense->category] ?? $expense->category),
            'title' => $expense->title,
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date?->format('d M Y'),
            'expense_date_iso' => $expense->expense_date?->toDateString(),
            'payment_method' => $expense->payment_method,
            'payment_method_label' => str_replace('_', ' ', $expense->payment_method),
            'notes' => $expense->notes,
            'recorded_by_name' => $expense->recorder?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodMetrics(?int $branchId, Carbon $from, Carbon $to): array
    {
        $query = $this->expenseQuery($branchId)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);

        $totalExpenses = round((float) (clone $query)->sum('amount'), 2);
        $expenseCount = (int) (clone $query)->count();

        $cashQuery = (clone $query)->where('payment_method', 'cash');
        $cashTotal = round((float) (clone $cashQuery)->sum('amount'), 2);
        $cashCount = (int) (clone $cashQuery)->count();

        $bankQuery = (clone $query)->where('payment_method', 'bank_transfer');
        $bankTotal = round((float) (clone $bankQuery)->sum('amount'), 2);
        $bankCount = (int) (clone $bankQuery)->count();

        $labels = config('libcontrol.expense_categories', []);
        $topRow = (clone $query)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $topAmount = round((float) ($topRow?->total ?? 0), 2);
        $topLabel = $topRow
            ? (string) ($labels[$topRow->category] ?? $topRow->category)
            : '—';

        return [
            'total_expenses' => $totalExpenses,
            'expense_count' => $expenseCount,
            'cash_total' => $cashTotal,
            'cash_count' => $cashCount,
            'bank_transfer_total' => $bankTotal,
            'bank_transfer_count' => $bankCount,
            'top_category' => [
                'label' => $topLabel,
                'amount' => $topAmount,
                'share_pct' => $totalExpenses > 0 ? round(($topAmount / $totalExpenses) * 100, 1) : 0.0,
            ],
        ];
    }

    private function deltaPercent(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 0);
    }

    private function expenseQuery(?int $branchId)
    {
        return Expense::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
    }

    /**
     * @return array{total: float, count: int}
     */
    private function feeIncomeMetrics(?int $branchId, Carbon $from, Carbon $to): array
    {
        $query = $this->feePaymentQuery($branchId)
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()]);

        return [
            'total' => round((float) (clone $query)->sum('amount'), 2),
            'count' => (int) (clone $query)->count(),
        ];
    }

    private function feePaymentQuery(?int $branchId)
    {
        return FeePayment::query()
            ->with([
                'booking.student:id,student_code,name',
                'booking.seat.hall.branch:id,name',
                'receivedBy:id,name',
            ])
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('booking.seat.hall', fn ($hall) => $hall->where('branch_id', $branchId));
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function feePeriodSummary(?int $branchId, Carbon $from, Carbon $to): array
    {
        $income = $this->feeIncomeMetrics($branchId, $from, $to);

        return array_merge(
            $this->feeCollectionMetrics($branchId, $from, $to),
            [
                'payment_count' => $income['count'],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function chartData(
        ?int $branchId,
        Carbon $pageFrom,
        Carbon $pageTo,
        string $collectionPeriod = 'this_month',
        int $trendMonths = 6,
    ): array {
        [$collectionFrom, $collectionTo] = $this->resolveChartPeriod($collectionPeriod, $pageTo);

        return [
            'fee_collection' => $this->feeCollectionMetrics($branchId, $collectionFrom, $collectionTo),
            'fee_income_vs_expenses' => $this->monthlyFeeIncomeVsExpenses($branchId, $pageTo, $trendMonths),
            'income_expenses_profit' => $this->monthlyIncomeExpensesProfit($branchId, $pageTo, $trendMonths),
        ];
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    private function resolveChartPeriod(string $period, Carbon $pageTo): array
    {
        $end = $pageTo->copy()->endOfDay();
        $start = match ($period) {
            'last_3_months' => $end->copy()->subMonthsNoOverflow(2)->startOfMonth()->startOfDay(),
            'last_6_months' => $end->copy()->subMonthsNoOverflow(5)->startOfMonth()->startOfDay(),
            'this_year' => $end->copy()->startOfYear()->startOfDay(),
            default => $end->copy()->startOfMonth()->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * @return array<string, float|int|string|null>
     */
    private function feeCollectionMetrics(?int $branchId, Carbon $from, Carbon $to): array
    {
        $received = round($this->feeIncomeMetrics($branchId, $from, $to)['total'], 2);
        $scheduledDue = round($this->expectedFeeAmount($branchId, $from, $to), 2);

        $pending = max(0, round($scheduledDue - $received, 2));
        $collectedOnDue = $scheduledDue > 0 ? round(min($received, $scheduledDue), 2) : 0.0;
        $otherCollected = max(0, round($received - $scheduledDue, 2));

        $pieReceived = $scheduledDue > 0 ? $collectedOnDue : ($received > 0 ? $received : 0.0);
        $piePending = $pending;
        $pieTotal = $pieReceived + $piePending;

        $receivedPercentage = $scheduledDue > 0 && $pieTotal > 0
            ? round(($pieReceived / $pieTotal) * 100, 1)
            : ($received > 0 ? 100.0 : 0.0);
        $pendingPercentage = $pieTotal > 0 ? round(($piePending / $pieTotal) * 100, 1) : 0.0;

        return [
            'expected' => $scheduledDue,
            'scheduled_due' => $scheduledDue,
            'received' => $received,
            'collected_on_due' => $collectedOnDue,
            'other_collected' => $otherCollected,
            'pending' => $pending,
            'pie_received' => round($pieReceived, 2),
            'pie_pending' => round($piePending, 2),
            'received_percentage' => $receivedPercentage,
            'pending_percentage' => $pendingPercentage,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'has_data' => $scheduledDue > 0 || $received > 0,
        ];
    }

    private function expectedFeeAmount(?int $branchId, Carbon $from, Carbon $to): float
    {
        $installmentExpected = FeeInstallment::query()
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('booking', function ($query) use ($branchId) {
                $query->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled')
                    ->where('fee_amount', '>', 0);

                if ($branchId) {
                    $query->whereHas('seat.hall', fn ($hall) => $hall->where('branch_id', $branchId));
                }
            })
            ->sum('amount');

        $oneTimeExpected = SeatBooking::query()
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->where('fee_amount', '>', 0)
            ->where('fee_type', 'one_time')
            ->whereBetween('joining_date', [$from->toDateString(), $to->toDateString()])
            ->whereDoesntHave('installments')
            ->when($branchId, fn ($query) => $query->whereHas('seat.hall', fn ($hall) => $hall->where('branch_id', $branchId)))
            ->sum('fee_amount');

        $recurringExpected = $this->recurringFeeExpectation($branchId, $from, $to);

        return round((float) $installmentExpected + (float) $oneTimeExpected + $recurringExpected, 2);
    }

    private function recurringFeeExpectation(?int $branchId, Carbon $from, Carbon $to): float
    {
        $bookings = SeatBooking::query()
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->where('fee_amount', '>', 0)
            ->whereIn('fee_type', ['monthly', 'installment'])
            ->whereDoesntHave('installments')
            ->whereDate('joining_date', '<=', $to->toDateString())
            ->where(function ($query) use ($from) {
                $query->whereNull('plan_expiry_date')
                    ->orWhereDate('plan_expiry_date', '>=', $from->toDateString());
            })
            ->when($branchId, fn ($query) => $query->whereHas('seat.hall', fn ($hall) => $hall->where('branch_id', $branchId)))
            ->get(['id', 'fee_amount', 'joining_date', 'plan_expiry_date']);

        if ($bookings->isEmpty()) {
            return 0.0;
        }

        $total = 0.0;
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($to)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $rangeStart = $monthStart->greaterThan($from) ? $monthStart : $from->copy();
            $rangeEnd = $monthEnd->lessThan($to) ? $monthEnd : $to->copy();

            foreach ($bookings as $booking) {
                if ($this->bookingActiveBetween($booking, $rangeStart, $rangeEnd)) {
                    $total += (float) $booking->fee_amount;
                }
            }

            $cursor->addMonthNoOverflow();
        }

        return round($total, 2);
    }

    private function bookingActiveBetween(SeatBooking $booking, Carbon $from, Carbon $to): bool
    {
        $joining = $booking->joining_date;
        $expiry = $booking->plan_expiry_date;

        if ($joining && $joining->gt($to)) {
            return false;
        }

        if ($expiry && $expiry->lt($from)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{labels: list<string>, student_fee_income: list<float>, total_expenses: list<float>, has_data: bool}
     */
    private function monthlyFeeIncomeVsExpenses(?int $branchId, Carbon $pageTo, int $months): array
    {
        $series = $this->monthlySeriesWindow($pageTo, $months);
        $feeTotals = $this->sumByMonth(
            $this->feePaymentQuery($branchId)
                ->whereBetween('payment_date', [$series['start']->toDateString(), $series['end']->toDateString()])
                ->get(['payment_date', 'amount']),
            'payment_date',
            'amount',
        );
        $expenseTotals = $this->sumByMonth(
            $this->expenseQuery($branchId)
                ->whereBetween('expense_date', [$series['start']->toDateString(), $series['end']->toDateString()])
                ->get(['expense_date', 'amount']),
            'expense_date',
            'amount',
        );

        $labels = [];
        $studentFeeIncome = [];
        $totalExpenses = [];

        foreach ($series['months'] as $month) {
            $labels[] = $month['label'];
            $studentFeeIncome[] = round((float) ($feeTotals[$month['key']] ?? 0), 2);
            $totalExpenses[] = round((float) ($expenseTotals[$month['key']] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'student_fee_income' => $studentFeeIncome,
            'total_expenses' => $totalExpenses,
            'has_data' => array_sum($studentFeeIncome) > 0 || array_sum($totalExpenses) > 0,
        ];
    }

    /**
     * @return array{labels: list<string>, total_income: list<float>, total_expenses: list<float>, total_profit: list<float>, has_data: bool}
     */
    private function monthlyIncomeExpensesProfit(?int $branchId, Carbon $pageTo, int $months): array
    {
        $trend = $this->monthlyFeeIncomeVsExpenses($branchId, $pageTo, $months);

        $totalIncome = [];
        $totalExpenses = [];
        $totalProfit = [];

        foreach ($trend['labels'] as $index => $label) {
            $income = round((float) ($trend['student_fee_income'][$index] ?? 0), 2);
            $expenses = round((float) ($trend['total_expenses'][$index] ?? 0), 2);
            $totalIncome[] = $income;
            $totalExpenses[] = $expenses;
            $totalProfit[] = round($income - $expenses, 2);
        }

        return [
            'labels' => $trend['labels'],
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'total_profit' => $totalProfit,
            'has_data' => array_sum($totalIncome) > 0 || array_sum($totalExpenses) > 0,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, months: list<array{key: string, label: string}>}
     */
    private function monthlySeriesWindow(Carbon $pageTo, int $months): array
    {
        $months = max(1, min(12, $months));
        $end = $pageTo->copy()->endOfMonth();
        $start = $end->copy()->subMonthsNoOverflow($months - 1)->startOfMonth();

        $cursor = $start->copy();
        $monthBuckets = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $monthBuckets[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
            ];
            $cursor->addMonthNoOverflow();
        }

        return [
            'start' => $start,
            'end' => $end,
            'months' => $monthBuckets,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $rows
     * @return array<string, float>
     */
    private function sumByMonth($rows, string $dateColumn, string $amountColumn): array
    {
        $totals = [];

        foreach ($rows as $row) {
            $date = $row->{$dateColumn};

            if (! $date) {
                continue;
            }

            $key = Carbon::parse($date)->format('Y-m');
            $totals[$key] = ($totals[$key] ?? 0) + (float) $row->{$amountColumn};
        }

        return $totals;
    }
}
