<?php

namespace App\Services\EmailAutomation;

use App\Mail\AutomationAlertMail;
use App\Models\FeeInstallment;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\ProfitLossService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class FinanceDigestService
{
    public function __construct(
        private OwnerRecipientResolver $owners,
        private ProfitLossService $profitLoss,
        private EmailAutomationLogger $logger,
    ) {}

    public function sendWeeklyDigest(?int $branchId = null, bool $dryRun = false): int
    {
        $settings = PlatformSetting::current();
        if (! ($settings->email_finance_digest_enabled ?? true)) {
            return 0;
        }

        $tz = config('libcontrol.timezone', 'Asia/Kolkata');
        $today = Carbon::now($tz)->startOfDay();
        $weekKey = $today->format('o-\WW');
        $templateKey = 'finance_weekly_digest_'.$weekKey;

        $admins = $this->owners->clientAdmins();
        if ($admins === []) {
            return 0;
        }

        $maxPerRun = max(1, (int) config('libcontrol.finance_digest.max_per_run', 25));
        $delayMs = max(0, (int) config('libcontrol.finance_digest.delay_ms', 2500));

        $snapshot = $this->buildSnapshot($branchId, $today);
        $libraryName = $settings->displayName();
        $subject = $this->subjectFor($snapshot, $weekKey);
        $body = $this->bodyFor($snapshot, $today);
        $context = $this->contextFor($snapshot);
        $ctaUrl = url('/profit-loss');

        $sent = 0;

        foreach ($admins as $admin) {
            if ($sent >= $maxPerRun) {
                break;
            }

            $userId = (int) $admin['id'];
            $email = (string) $admin['email'];

            if ($this->logger->wasSent($templateKey, User::class, $userId)) {
                continue;
            }

            if ($dryRun) {
                $sent++;
                continue;
            }

            try {
                Mail::to($email)->send(new AutomationAlertMail(
                    subjectLine: $subject,
                    headline: 'Weekly finance digest',
                    bodyText: $body,
                    libraryName: $libraryName,
                    context: $context,
                    ctaLabel: 'Open Profit & Loss',
                    ctaUrl: $ctaUrl,
                ));
            } catch (\Throwable $exception) {
                report($exception);
                continue;
            }

            if ($this->logger->record(
                $templateKey,
                $email,
                User::class,
                $userId,
                $branchId,
                [
                    'week' => $weekKey,
                    'priority' => $snapshot['priority'],
                    'overdue_count' => $snapshot['overdue_count'],
                ],
            )) {
                $sent++;
            }

            if ($delayMs > 0 && $sent < $maxPerRun && ! app()->environment('testing')) {
                usleep($delayMs * 1000);
            }
        }

        return $sent;
    }

    /**
     * @return array{
     *     priority: string,
     *     period_from: string,
     *     period_to: string,
     *     fee_income_total: float,
     *     total_expenses: float,
     *     net_profit: float,
     *     overdue_count: int,
     *     overdue_amount: float,
     *     due_this_week_count: int,
     *     due_this_week_amount: float
     * }
     */
    public function buildSnapshot(?int $branchId, Carbon $today): array
    {
        $to = $today->copy()->endOfDay();
        $from = $today->copy()->subDays(6)->startOfDay();

        $summary = $this->profitLoss->summary($branchId, $from, $to);
        $overdue = $this->unpaidInstallmentStats($branchId, null, $today->copy()->subDay());
        $dueThisWeek = $this->unpaidInstallmentStats($branchId, $from, $to);

        $priority = 'low';
        if ($overdue['count'] > 0 || $overdue['amount'] >= 5000) {
            $priority = 'high';
        } elseif ($dueThisWeek['count'] > 0 || (float) $summary['fee_income_total'] > 0) {
            $priority = 'medium';
        }

        return [
            'priority' => $priority,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'fee_income_total' => (float) $summary['fee_income_total'],
            'total_expenses' => (float) $summary['total_expenses'],
            'net_profit' => (float) $summary['net_profit'],
            'overdue_count' => $overdue['count'],
            'overdue_amount' => $overdue['amount'],
            'due_this_week_count' => $dueThisWeek['count'],
            'due_this_week_amount' => $dueThisWeek['amount'],
        ];
    }

    /**
     * @return array{count: int, amount: float}
     */
    private function unpaidInstallmentStats(?int $branchId, ?Carbon $from, Carbon $to): array
    {
        $query = FeeInstallment::query()
            ->whereNull('paid_at')
            ->whereDate('due_date', '<=', $to->toDateString())
            ->when($from, fn ($builder) => $builder->whereDate('due_date', '>=', $from->toDateString()))
            ->whereHas('booking', function ($bookingQuery) use ($branchId) {
                $bookingQuery
                    ->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled');

                if ($branchId) {
                    $bookingQuery->whereHas('seat.hall', fn ($hall) => $hall->where('branch_id', $branchId));
                }
            });

        return [
            'count' => (int) (clone $query)->count(),
            'amount' => round((float) (clone $query)->sum('amount'), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function subjectFor(array $snapshot, string $weekKey): string
    {
        $prefix = match ($snapshot['priority']) {
            'high' => '[High] ',
            'medium' => '[Medium] ',
            default => '[Low] ',
        };

        return $prefix.'Weekly finance digest ('.$weekKey.')';
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function bodyFor(array $snapshot, Carbon $today): string
    {
        $fromLabel = Carbon::parse($snapshot['period_from'], $today->timezone)->format('d M Y');
        $toLabel = Carbon::parse($snapshot['period_to'], $today->timezone)->format('d M Y');

        $lines = [
            'Hi,',
            '',
            "Finance summary for {$fromLabel} – {$toLabel} (IST).",
            '',
            'HIGH PRIORITY',
            '- Overdue installments: '.$snapshot['overdue_count'].' (₹'.$this->money($snapshot['overdue_amount']).')',
            $snapshot['overdue_count'] > 0
                ? '- Action: follow up on overdue fees before they age further.'
                : '- No overdue installments. Keep collections on track.',
            '',
            'MEDIUM PRIORITY',
            '- Fees collected (7 days): ₹'.$this->money($snapshot['fee_income_total']),
            '- Unpaid dues in period: '.$snapshot['due_this_week_count'].' (₹'.$this->money($snapshot['due_this_week_amount']).')',
            '- Expenses (7 days): ₹'.$this->money($snapshot['total_expenses']),
            '',
            'LOW PRIORITY',
            '- Net (fees − expenses): ₹'.$this->money($snapshot['net_profit']),
            '- Tip: review Profit & Loss for the full ledger and trends.',
        ];

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, string>
     */
    private function contextFor(array $snapshot): array
    {
        return [
            'Priority' => strtoupper((string) $snapshot['priority']),
            'Period (IST)' => $snapshot['period_from'].' → '.$snapshot['period_to'],
            'Overdue' => $snapshot['overdue_count'].' / ₹'.$this->money($snapshot['overdue_amount']),
            'Collected (7d)' => '₹'.$this->money($snapshot['fee_income_total']),
            'Expenses (7d)' => '₹'.$this->money($snapshot['total_expenses']),
            'Net (7d)' => '₹'.$this->money($snapshot['net_profit']),
        ];
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }
}
