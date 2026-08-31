<?php

namespace App\Services;

use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\SeatBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FeeService
{
    public function __construct(private PlanExpiryService $planExpiryService) {}

    public function baseQuery(?int $branchId): Builder
    {
        return SeatBooking::query()
            ->with([
                'student:id,student_code,name,phone,email',
                'seat.hall:id,name,branch_id',
                'seat.hall.branch:id,name',
                'installments',
                'payments' => fn ($query) => $query->orderByDesc('payment_date')->orderByDesc('created_at'),
            ])
            ->when($branchId, fn ($query) => $query->whereHas('seat.hall', fn ($hallQuery) => $hallQuery->where('branch_id', $branchId)))
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled');
    }

    /**
     * @return array{expiring_soon: Collection, expired: Collection, active: Collection}
     */
    public function overviewForBranch(?int $branchId): array
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);
        $base = $this->baseQuery($branchId);

        return [
            'expiring_soon' => (clone $base)
                ->whereDate('plan_expiry_date', '>=', $today)
                ->whereDate('plan_expiry_date', '<=', $soon)
                ->orderBy('plan_expiry_date')
                ->get(),
            'expired' => (clone $base)
                ->whereDate('plan_expiry_date', '<', $today)
                ->orderByDesc('plan_expiry_date')
                ->get(),
            'active' => (clone $base)
                ->whereDate('plan_expiry_date', '>', $soon)
                ->orderBy('plan_expiry_date')
                ->get(),
        ];
    }

    public function normalizeFeeType(string $feeType): string
    {
        return $this->planExpiryService->normalize($feeType);
    }

    public function normalizePaymentPlan(?string $plan, string $feeType = ''): string
    {
        $type = $this->normalizeFeeType($feeType);

        if ($type === 'one_time') {
            return 'full';
        }

        if ($feeType === 'installment' || $plan === 'installments') {
            return 'installments';
        }

        return 'full';
    }

    public function normalizeFrequency(?string $frequency): string
    {
        return match ($frequency) {
            'weekly', 'quarterly', 'half_yearly', 'yearly', 'custom', 'monthly' => (string) $frequency,
            default => 'monthly',
        };
    }

    public function resolveExpiry(string $feeType, Carbon $joining, ?Carbon $customEnd = null): Carbon
    {
        return $this->planExpiryService->calculate($feeType, $joining, $customEnd);
    }

    public function planStatus(SeatBooking $booking): string
    {
        if ($booking->cancelled_at || $booking->status === 'cancelled') {
            return 'cancelled';
        }

        $today = Carbon::today();
        $joining = $booking->joining_date;
        $expiry = $booking->plan_expiry_date;

        if ($joining && $joining->gt($today)) {
            return 'upcoming';
        }

        if ($expiry && $expiry->lt($today)) {
            return 'expired';
        }

        $soon = $today->copy()->addDays(7);

        if ($expiry && $expiry->lte($soon)) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function isFlexibleInstallment(SeatBooking $booking): bool
    {
        return $this->isInstallmentPlan($booking)
            && $this->normalizeFrequency($booking->installment_frequency) === 'custom';
    }

    public function paidAmount(SeatBooking $booking): float
    {
        if ($booking->fee_paid_at) {
            return round((float) $booking->fee_amount, 2);
        }

        $stored = round((float) ($booking->amount_paid ?? 0), 2);

        if ($this->isFlexibleInstallment($booking) || ! $this->isInstallmentPlan($booking)) {
            return $stored;
        }

        $installments = $booking->relationLoaded('installments')
            ? $booking->installments
            : $booking->installments()->orderBy('installment_number')->get();

        $fromInstallments = round((float) $installments->whereNotNull('paid_at')->sum('amount'), 2);

        return max($stored, $fromInstallments);
    }

    public function amountDue(SeatBooking $booking): float
    {
        return max(0, round((float) $booking->fee_amount - $this->paidAmount($booking), 2));
    }

    public function paymentStatus(SeatBooking $booking): string
    {
        $total = round((float) $booking->fee_amount, 2);
        $paid = $this->paidAmount($booking);

        if ($paid <= 0) {
            $due = $booking->joining_date ?? $booking->plan_expiry_date;

            if ($this->isInstallmentPlan($booking) && ! $this->isFlexibleInstallment($booking)) {
                $installments = $booking->relationLoaded('installments')
                    ? $booking->installments
                    : $booking->installments()->orderBy('installment_number')->get();
                $overdue = $installments->first(fn (FeeInstallment $row) => $row->paid_at === null && $row->due_date && $row->due_date->lt(Carbon::today()));

                return $overdue ? 'overdue' : 'unpaid';
            }

            if ($due && $due->lt(Carbon::today())) {
                return 'overdue';
            }

            return 'unpaid';
        }

        if ($paid + 0.009 >= $total) {
            return 'paid';
        }

        return 'partial';
    }

    public function isInstallmentPlan(SeatBooking $booking): bool
    {
        if ($booking->payment_plan === 'installments' || $booking->fee_type === 'installment') {
            return true;
        }

        if ($booking->relationLoaded('installments')) {
            return $booking->installments->isNotEmpty();
        }

        return $booking->installments()->exists();
    }

    public function frequencyIntervalMonths(string $frequency): int
    {
        return match ($this->normalizeFrequency($frequency)) {
            'quarterly' => 3,
            'half_yearly' => 6,
            'yearly' => 12,
            'weekly' => 0,
            default => 1,
        };
    }

    public function addInstallmentDue(Carbon $firstDue, string $frequency, int $index): Carbon
    {
        $frequency = $this->normalizeFrequency($frequency);
        $due = $firstDue->copy();

        if ($frequency === 'weekly') {
            return $due->addWeeks($index);
        }

        return $due->addMonths($this->frequencyIntervalMonths($frequency) * $index);
    }

    /**
     * @return list<array{number: int, amount: float, due: Carbon}>
     */
    public function buildSchedule(float $total, int $count, string $frequency, Carbon $firstDue, int $minCount = 2): array
    {
        $count = max($minCount, min(12, max(1, $count)));
        $cents = (int) round($total * 100);
        $base = intdiv($cents, $count);
        $schedule = [];

        for ($i = 0; $i < $count; $i++) {
            $amountCents = $i === $count - 1 ? $cents - ($base * ($count - 1)) : $base;

            $schedule[] = [
                'number' => $i + 1,
                'amount' => round($amountCents / 100, 2),
                'due' => $this->addInstallmentDue($firstDue, $frequency, $i),
            ];
        }

        return $schedule;
    }

    public function suggestedInstallmentCount(Carbon $firstDue, Carbon $planEnd, string $frequency): int
    {
        $frequency = $this->normalizeFrequency($frequency);

        if ($frequency === 'weekly') {
            $weeks = max(1, (int) $firstDue->diffInWeeks($planEnd) + 1);

            return max(2, min(12, $weeks));
        }

        $interval = max(1, $this->frequencyIntervalMonths($frequency));
        $months = max(1, (int) $firstDue->diffInMonths($planEnd) + 1);

        return max(2, min(12, (int) ceil($months / $interval)));
    }

    public function syncInstallments(
        SeatBooking $booking,
        int $count,
        string $frequency = 'monthly',
        ?Carbon $firstDue = null,
    ): void {
        $frequency = $this->normalizeFrequency($frequency);

        if ($frequency === 'custom') {
            $booking->installments()->whereNull('paid_at')->delete();

            return;
        }

        $paid = $booking->installments()->whereNotNull('paid_at')->orderBy('installment_number')->get();
        $booking->installments()->whereNull('paid_at')->delete();

        $total = round((float) $booking->fee_amount, 2);
        $paidAmount = round((float) $paid->sum('amount'), 2);
        $remainingAmount = max(0, round($total - $paidAmount, 2));
        $remainingCount = max(0, $count - $paid->count());

        if ($remainingCount === 0 || $remainingAmount <= 0) {
            return;
        }

        $minCount = $paid->isEmpty() ? 2 : 1;
        $start = $firstDue?->copy()
            ?? ($paid->last()?->due_date
                ? $this->addInstallmentDue($paid->last()->due_date, $frequency, 1)
                : null)
            ?? $booking->joining_date?->copy()
            ?? Carbon::today();

        $schedule = $this->buildSchedule(
            $remainingAmount,
            max($minCount, $remainingCount),
            $frequency,
            $start,
            $minCount,
        );
        $nextNumber = ((int) $paid->max('installment_number')) + 1;

        foreach ($schedule as $index => $row) {
            FeeInstallment::query()->create([
                'seat_booking_id' => $booking->id,
                'installment_number' => $nextNumber + $index,
                'amount' => $row['amount'],
                'due_date' => $row['due'],
                'paid_at' => null,
            ]);
        }
    }

    public function applyInstallmentCoverage(SeatBooking $booking): void
    {
        if (! $this->isInstallmentPlan($booking) || $this->isFlexibleInstallment($booking)) {
            return;
        }

        $booking->loadMissing('installments');
        $coverage = $this->paidAmount($booking);
        $running = 0.0;

        foreach ($booking->installments->sortBy('installment_number') as $installment) {
            $running = round($running + (float) $installment->amount, 2);

            if ($coverage + 0.009 >= $running) {
                if (! $installment->paid_at) {
                    $installment->update(['paid_at' => now()]);
                }
            }
        }
    }

    public function recordPayment(SeatBooking $booking, float $amount, array $meta = []): SeatBooking
    {
        $amount = round(max(0, $amount), 2);
        $due = $this->amountDue($booking);
        $amount = min($amount, $due);

        if ($amount > 0) {
            FeePayment::query()->create([
                'seat_booking_id' => $booking->id,
                'amount' => $amount,
                'payment_method' => $meta['payment_method'] ?? 'cash',
                'payment_date' => isset($meta['payment_date'])
                    ? Carbon::parse($meta['payment_date'])->toDateString()
                    : Carbon::today()->toDateString(),
                'reference' => $meta['reference'] ?? null,
                'notes' => $meta['notes'] ?? null,
                'received_by' => $meta['received_by'] ?? null,
            ]);
        }

        $previousPaid = round((float) ($booking->amount_paid ?? 0), 2);
        $paid = round($previousPaid + $amount, 2);
        $total = round((float) $booking->fee_amount, 2);

        $booking->update([
            'amount_paid' => min($paid, $total),
            // Never overwrite fee_amount — only track payments via amount_paid + fee_payments.
            'fee_paid_at' => $paid + 0.009 >= $total ? ($booking->fee_paid_at ?? now()) : null,
        ]);

        $booking = $booking->fresh('installments');
        $this->applyInstallmentCoverage($booking);

        return $booking->fresh(['installments', 'payments']);
    }

    public function markInstallmentPaid(FeeInstallment $installment): FeeInstallment
    {
        if (! $installment->paid_at) {
            $installment->update(['paid_at' => now()]);
        }

        $booking = $installment->booking()->with('installments')->first();

        if ($booking) {
            $fromInstallments = round((float) $booking->installments->whereNotNull('paid_at')->sum('amount'), 2);
            $paid = max(round((float) ($booking->amount_paid ?? 0), 2), $fromInstallments);
            $booking->update([
                'amount_paid' => $paid,
                'fee_paid_at' => $paid + 0.009 >= (float) $booking->fee_amount ? ($booking->fee_paid_at ?? now()) : null,
            ]);
            $this->applyInstallmentCoverage($booking->fresh('installments'));
        }

        return $installment->fresh();
    }

    public function markPaid(SeatBooking $booking): SeatBooking
    {
        $booking->update([
            'amount_paid' => $booking->fee_amount,
            'fee_paid_at' => $booking->fee_paid_at ?? now(),
        ]);

        $booking->installments()->whereNull('paid_at')->get()->each(function (FeeInstallment $row) {
            $row->update(['paid_at' => now()]);
        });

        return $booking->fresh('installments');
    }

    public function feeTypeLabel(string $type): string
    {
        return match ($this->normalizeFeeType($type)) {
            'yearly' => 'Yearly',
            'membership' => 'Membership',
            'one_time' => 'One-time',
            'custom' => 'Custom',
            default => 'Monthly',
        };
    }

    public function frequencyLabel(?string $frequency): string
    {
        return match ($this->normalizeFrequency($frequency)) {
            'weekly' => 'Weekly',
            'quarterly' => 'Quarterly',
            'half_yearly' => 'Half-Yearly',
            'yearly' => 'Yearly',
            'custom' => 'Custom',
            default => 'Monthly',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeRow(SeatBooking $booking): array
    {
        $installments = $booking->relationLoaded('installments')
            ? $booking->installments->sortBy('installment_number')->values()
            : $booking->installments()->orderBy('installment_number')->get();

        $paidAmount = $this->paidAmount($booking);
        $feeType = $this->normalizeFeeType((string) $booking->fee_type);
        $paymentPlan = $this->normalizePaymentPlan($booking->payment_plan, (string) $booking->fee_type);
        $frequency = $this->normalizeFrequency($booking->installment_frequency);
        $isFlexible = $paymentPlan === 'installments' && $frequency === 'custom';

        if ($paymentPlan === 'installments' && ! $isFlexible && $paidAmount > 0) {
            $runningCheck = 0.0;
            $needsHeal = false;
            foreach ($installments as $row) {
                $runningCheck = round($runningCheck + (float) $row->amount, 2);
                if ($paidAmount + 0.009 >= $runningCheck && $row->paid_at === null) {
                    $needsHeal = true;
                    break;
                }
            }
            if ($needsHeal) {
                $this->applyInstallmentCoverage($booking);
                $booking = $booking->fresh(['student', 'seat.hall.branch', 'installments']) ?? $booking;
                $installments = $booking->installments->sortBy('installment_number')->values();
                $paidAmount = $this->paidAmount($booking);
            }
        }

        $paymentStatus = $this->paymentStatus($booking);
        $planStatus = $this->planStatus($booking);
        $totalCount = $installments->count();
        $serializedInstallments = [];
        $coverageLeft = $paidAmount;
        $paidCount = 0;

        foreach ($installments as $row) {
            $amount = round((float) $row->amount, 2);
            $applied = min($amount, max(0, $coverageLeft));
            $coverageLeft = round(max(0, $coverageLeft - $applied), 2);
            $fullyPaid = $applied + 0.009 >= $amount || $row->paid_at !== null;
            if ($fullyPaid) {
                $paidCount++;
                $applied = $amount;
            }
            $status = 'pending';
            if ($fullyPaid) {
                $status = 'paid';
            } elseif ($applied > 0) {
                $status = 'partial';
            } elseif ($row->due_date && $row->due_date->lt(Carbon::today())) {
                $status = 'overdue';
            }

            $serializedInstallments[] = [
                'id' => $row->id,
                'number' => $row->installment_number,
                'amount' => $row->amount,
                'due_date' => $row->due_date?->format('M d, Y'),
                'due_date_iso' => $row->due_date?->toDateString(),
                'paid' => $fullyPaid,
                'paid_amount' => round($applied, 2),
                'remaining_amount' => max(0, round($amount - $applied, 2)),
                'status' => $status,
                'paid_at' => $row->paid_at?->format('M d, Y'),
            ];
        }

        $payments = $booking->relationLoaded('payments')
            ? $booking->payments
            : $booking->payments()->orderByDesc('payment_date')->orderByDesc('created_at')->get();

        $serializedPayments = $payments->map(function (FeePayment $payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_method_label' => ucfirst(str_replace('_', ' ', (string) ($payment->payment_method ?? 'cash'))),
                'payment_date' => $payment->payment_date?->format('M d, Y'),
                'payment_time' => $payment->created_at?->format('g:i A'),
                'recorded_at' => $payment->created_at?->format('M d, Y g:i A'),
                'reference' => $payment->reference,
                'notes' => $payment->notes,
            ];
        })->values()->all();

        return [
            'id' => $booking->id,
            'student_code' => $booking->student?->student_code,
            'student_name' => $booking->student?->name,
            'student_phone' => $booking->student?->phone,
            'hall_name' => $booking->seat?->hall?->name,
            'branch_name' => $booking->seat?->hall?->branch?->name,
            'seat_number' => $booking->seat?->seat_number,
            'time_slot' => $booking->time_slot,
            'fee_type' => $feeType,
            'fee_type_label' => $this->feeTypeLabel($feeType),
            'fee_amount' => $booking->fee_amount,
            'joining_date' => $booking->joining_date?->format('M d, Y'),
            'joining_date_iso' => $booking->joining_date?->toDateString(),
            'plan_expiry_date' => $feeType === 'one_time' ? null : $booking->plan_expiry_date?->format('M d, Y'),
            'plan_expiry_date_iso' => $booking->plan_expiry_date?->toDateString(),
            'plan_status' => $planStatus,
            'plan_status_label' => str_replace('_', ' ', $planStatus),
            'payment_status' => $paymentStatus,
            'payment_status_label' => str_replace('_', ' ', $paymentStatus),
            'payment_plan' => $paymentPlan,
            'payment_plan_label' => $paymentPlan === 'installments' ? 'Installments' : 'Full payment',
            'membership_mode' => $booking->membership_mode,
            'student_id' => $booking->student_id,
            'hall_id' => $booking->seat?->hall_id,
            'seat_id' => $booking->seat_id,
            'time_slot_label' => str_replace('_', ' ', $booking->time_slot ?? ''),
            'is_installment' => $paymentPlan === 'installments',
            'is_flexible_installment' => $isFlexible,
            'installment_count' => $isFlexible ? null : ($totalCount ?: null),
            'installment_frequency' => $frequency,
            'installment_frequency_label' => $this->frequencyLabel($frequency),
            'first_due_date' => $installments->first()?->due_date?->toDateString()
                ?: ($booking->joining_date?->toDateString()),
            'installments_paid' => $paidCount,
            'installment_status' => $paymentStatus,
            'amount_paid' => $paidAmount,
            'amount_due' => $this->amountDue($booking),
            'fee_paid_at' => $booking->fee_paid_at?->toDateString(),
            'installments' => $serializedInstallments,
            'payments' => $serializedPayments,
        ];
    }
}
