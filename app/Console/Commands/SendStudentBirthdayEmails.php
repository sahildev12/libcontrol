<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\StudentEmailNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendStudentBirthdayEmails extends Command
{
    protected $signature = 'libcontrol:send-birthday-emails';

    protected $description = 'Send birthday emails to students with an email address';

    public function handle(StudentEmailNotificationService $notifications): int
    {
        $today = Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'));

        $students = Student::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->where('status', 'active')
            ->get();

        $sent = 0;

        foreach ($students as $student) {
            if (blank($student->effectiveEmail()) && blank($student->email)) {
                continue;
            }

            $notifications->sendBirthday($student);
            $sent++;
        }

        $this->info("Birthday emails processed for {$sent} student(s).");

        return self::SUCCESS;
    }
}
