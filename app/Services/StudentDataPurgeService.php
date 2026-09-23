<?php

namespace App\Services;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Models\FeeInstallment;
use App\Models\FeePayment;
use App\Models\SeatBooking;
use App\Models\Student;
use App\Models\StudentRegistrationInvite;
use Illuminate\Support\Facades\DB;

class StudentDataPurgeService
{
    public function purgeAll(): int
    {
        $studentIds = Student::query()->pluck('id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $bookingIds = SeatBooking::query()->whereIn('student_id', $studentIds)->pluck('id');

        if ($bookingIds->isNotEmpty()) {
            FeePayment::query()->whereIn('seat_booking_id', $bookingIds)->delete();
            FeeInstallment::query()->whereIn('seat_booking_id', $bookingIds)->delete();
            SeatBooking::query()->whereIn('id', $bookingIds)->delete();
        }

        AttendanceRecord::query()->whereIn('student_id', $studentIds)->delete();

        StudentRegistrationInvite::query()->whereIn('student_id', $studentIds)->delete();

        DB::table('personal_access_tokens')
            ->where('tokenable_type', Student::class)
            ->whereIn('tokenable_id', $studentIds)
            ->delete();

        $count = $studentIds->count();

        Student::query()->whereIn('id', $studentIds)->delete();

        return $count;
    }
}
