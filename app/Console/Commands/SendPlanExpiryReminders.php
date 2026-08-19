<?php

namespace App\Console\Commands;

use App\Mail\PlanExpiryReminderMail;
use App\Models\Branch;
use App\Models\SeatBooking;
use App\Services\BranchBrandService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendPlanExpiryReminders extends Command
{
    protected $signature = 'libspace:send-expiry-reminders {--branch= : Limit to a branch ID}';

    protected $description = 'Email students whose plan expiry is approaching (per branch settings, Asia/Kolkata day boundary)';

    public function handle(BranchBrandService $branchBrandService): int
    {
        $today = Carbon::today(config('libspace.timezone', 'Asia/Kolkata'));
        $branchQuery = Branch::query();

        if ($this->option('branch')) {
            $branchQuery->where('id', $this->option('branch'));
        }

        $sent = 0;

        foreach ($branchQuery->cursor() as $branch) {
            $daysBefore = (int) ($branch->expiry_reminder_days ?: config('libspace.defaults.expiry_reminder_days', 10));
            $targetDate = $today->copy()->addDays($daysBefore);

            $bookings = SeatBooking::query()
                ->with(['student:id,name,email', 'seat.hall:id,name'])
                ->whereHas('seat.hall', fn ($query) => $query->where('branch_id', $branch->id))
                ->whereNull('cancelled_at')
                ->where('status', '!=', 'cancelled')
                ->whereDate('plan_expiry_date', $targetDate)
                ->whereNull('expiry_reminder_sent_at')
                ->get();

            foreach ($bookings as $booking) {
                $email = $booking->student?->email;

                if (! $email) {
                    continue;
                }

                Mail::to($email)->send(new PlanExpiryReminderMail(
                    $booking,
                    $daysBefore,
                    $branchBrandService->displayName($branch),
                ));

                $booking->update(['expiry_reminder_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Sent {$sent} expiry reminder email(s).");

        return self::SUCCESS;
    }
}
