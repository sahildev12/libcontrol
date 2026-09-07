<?php

namespace App\Services;

use App\Models\Expense;
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

        $totalExpenses = $current['total_expenses'];
        $topCategory = $current['top_category'];

        return [
            'total_expenses' => $totalExpenses,
            'total_expenses_delta_pct' => $this->deltaPercent($totalExpenses, $previous['total_expenses']),
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
}
