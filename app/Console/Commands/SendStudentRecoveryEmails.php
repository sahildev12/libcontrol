<?php

namespace App\Console\Commands;

use App\Models\EmailAutomationLog;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\StudentEmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SendStudentRecoveryEmails extends Command
{
    protected $signature = 'libcontrol:send-recovery-emails {--days=30 : Days since the student\'s last plan ended}';

    protected $description = 'Send one "we miss you" email to students whose last plan ended a while ago and who have not come back';

    public function handle(StudentEmailNotificationService $notifications): int
    {
        $days = max(7, (int) $this->option('days'));
        $cutoff = Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->subDays($days)->toDateString();

        $stillMembers = $this->liveBookings()
            ->where(fn (Builder $query) => $query
                ->whereNull('plan_expiry_date')
                ->orWhereDate('plan_expiry_date', '>=', $cutoff))
            ->distinct()
            ->pluck('student_id')
            ->all();

        $lastPlanEnded = $this->liveBookings()
            ->whereNotNull('plan_expiry_date')
            ->selectRaw('student_id, MAX(plan_expiry_date) as last_expiry')
            ->groupBy('student_id')
            ->pluck('last_expiry', 'student_id');

        $students = Student::query()
            ->where('status', 'active')
            ->whereIn('id', $lastPlanEnded->keys()->all())
            ->whereNotIn('id', $stillMembers)
            ->get();

        $sent = 0;

        foreach ($students as $student) {
            $key = 'student_recovery:'.Carbon::parse($lastPlanEnded[$student->id])->toDateString();

            if (EmailAutomationLog::alreadySent($key, Student::class, $student->id)) {
                continue;
            }

            if (! $notifications->sendRecovery($student)) {
                continue;
            }

            EmailAutomationLog::query()->create([
                'template_key' => $key,
                'recipient_email' => (string) ($student->effectiveEmail() ?: $student->email),
                'related_type' => Student::class,
                'related_id' => $student->id,
                'branch_id' => $student->branch_id,
                'sent_at' => now(),
            ]);
            $sent++;
        }

        $this->info("Recovery emails sent to {$sent} student(s).");

        return self::SUCCESS;
    }

    /**
     * @return Builder<SeatBooking>
     */
    private function liveBookings(): Builder
    {
        return SeatBooking::query()
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled');
    }
}
