<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FeePayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProfitLossService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?int $branchId, Carbon $from, Carbon $to): array
    {
        $revenue = $this->revenueBetween($branchId, $from, $to);
        $expenses = $this->expensesBetween($branchId, $from, $to);
        $profit = round($revenue - $expenses, 2);

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $profit,
            'margin_percent' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0,
            'expense_by_category' => $this->expensesByCategory($branchId, $from, $to),
        ];
    }

    public function revenueBetween(?int $branchId, Carbon $from, Carbon $to): float
    {
        return round((float) $this->feePaymentQuery($branchId)
            ->whereBetween('fee_payments.payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('fee_payments.amount'), 2);
    }

    public function expensesBetween(?int $branchId, Carbon $from, Carbon $to): float
    {
        return round((float) $this->expenseQuery($branchId)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount'), 2);
    }

    /**
     * @return Collection<int, array{category: string, label: string, total: float}>
     */
    public function expensesByCategory(?int $branchId, Carbon $from, Carbon $to): Collection
    {
        $labels = config('libcontrol.expense_categories', []);

        return $this->expenseQuery($branchId)
            ->selectRaw('category, SUM(amount) as total')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => (string) $row->category,
                'label' => (string) ($labels[$row->category] ?? $row->category),
                'total' => round((float) $row->total, 2),
            ]);
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

    private function feePaymentQuery(?int $branchId)
    {
        $query = FeePayment::query()
            ->join('seat_bookings', 'seat_bookings.id', '=', 'fee_payments.seat_booking_id')
            ->join('seats', 'seats.id', '=', 'seat_bookings.seat_id')
            ->join('halls', 'halls.id', '=', 'seats.hall_id');

        if ($branchId) {
            $query->where('halls.branch_id', $branchId);
        }

        return $query;
    }

    private function expenseQuery(?int $branchId)
    {
        return Expense::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId));
    }
}
