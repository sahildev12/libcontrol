<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--hours=48 : Look back this many hours}';

    protected $description = 'Confirm pending PhonePe payments with the Order Status API (webhook fallback)';

    public function handle(PaymentService $payments): int
    {
        if (! config('libcontrol.license_server.enabled')) {
            $this->info('Payments are reconciled on the Katsindia hub only.');

            return self::SUCCESS;
        }

        $pending = Payment::query()
            ->whereIn('status', [Payment::STATUS_CREATED, Payment::STATUS_PENDING])
            ->where('created_at', '>=', now()->subHours(max(1, (int) $this->option('hours'))))
            ->where('created_at', '<=', now()->subMinutes(2))
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($pending as $payment) {
            try {
                $result = $payments->refreshStatus($payment);
                $this->line("{$payment->merchant_order_id}: {$result->status}");
            } catch (\Throwable $e) {
                $this->warn("{$payment->merchant_order_id}: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$pending->count()} payment(s).");

        return self::SUCCESS;
    }
}
