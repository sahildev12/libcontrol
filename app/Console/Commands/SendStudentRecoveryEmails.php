<?php

namespace App\Console\Commands;

use App\Models\SeatBooking;
use App\Models\Student;
use App\Services\StudentEmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendStudentRecoveryEmails extends Command
{
    protected $signature = 'libcontrol:send-recovery-emails {--days=30 : Days since last active booking}';

    protected $description = 'Send recovery emails to inactive students who still have an email address';

    public function handle(StudentEmailNotificationService $notifications): int
    {
        $days = max(7, (int) $this->option('days'));
        $cutoff = Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->subDays($days)->toDateString();

        $activeStudentIds = SeatBooking::query()
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->whereDate('joining_date', '>=', $cutoff)
            ->pluck('student_id')
            ->unique()
            ->all();

        $students = Student::query()
            ->where('status', 'active')
            ->when($activeStudentIds !== [], fn ($query) => $query->whereNotIn('id', $activeStudentIds))
            ->get();

        $sent = 0;

        foreach ($students as $student) {
            if (blank($student->effectiveEmail()) && blank($student->email)) {
                continue;
            }

            $notifications->sendRecovery($student);
            $sent++;
        }

        $this->info("Recovery emails processed for {$sent} student(s).");

        return self::SUCCESS;
    }
}
