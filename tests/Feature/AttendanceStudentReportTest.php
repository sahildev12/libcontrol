<?php

namespace Tests\Feature;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\AttendanceTestCase;
use Tests\InstallsAttendanceAddon;

class AttendanceStudentReportTest extends AttendanceTestCase
{
    use DatabaseMigrations;
    use InstallsAttendanceAddon;

    public function test_student_attendance_report_page_loads(): void
    {
        Carbon::setTestNow('2026-09-18 10:00:00');

        $branch = Branch::factory()->create(['library_open_time' => '09:00']);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'student_code' => 'STU00123',
            'name' => 'Aarav Sharma',
            'status' => 'active',
        ]);

        $this->installAttendanceAddon();

        AttendanceRecord::query()->create([
            'branch_id' => $branch->id,
            'student_id' => $student->id,
            'attendance_date' => '2026-09-18',
            'check_in_at' => '2026-09-18 09:02:00',
            'method' => AttendanceRecord::METHOD_STUDENT_QR,
        ]);

        $this->actingAs($user)
            ->get(route('attendance.student-report', [
                'student' => $student,
                'date_from' => '2026-09-01',
                'date_to' => '2026-09-18',
            ]))
            ->assertOk()
            ->assertSee('Attendance Report', false)
            ->assertSee('Aarav Sharma', false)
            ->assertSee('STU00123', false)
            ->assertSee('Attendance Calendar', false)
            ->assertSee('Selected Day Details', false)
            ->assertDontSee('Daily attendance summary', false);

        Carbon::setTestNow();
    }

    public function test_student_attendance_report_data_endpoint_returns_summary(): void
    {
        Carbon::setTestNow('2026-09-18 10:00:00');

        $branch = Branch::factory()->create(['library_open_time' => '09:00']);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $this->installAttendanceAddon();

        AttendanceRecord::query()->create([
            'branch_id' => $branch->id,
            'student_id' => $student->id,
            'attendance_date' => '2026-09-18',
            'check_in_at' => '2026-09-18 09:02:00',
            'method' => AttendanceRecord::METHOD_STUDENT_QR,
        ]);

        $response = $this->actingAs($user)->getJson(route('attendance.student-report.data', [
            'student' => $student,
            'date_from' => '2026-09-16',
            'date_to' => '2026-09-18',
            'selected_date' => '2026-09-18',
        ]));

        $response->assertOk()
            ->assertJsonPath('student.name', $student->name)
            ->assertJsonPath('selected_day.status', 'present')
            ->assertJsonPath('selected_day.method_label', 'Student QR')
            ->assertJsonPath('summary.total_days', 3)
            ->assertJsonPath('summary.present', 1)
            ->assertJsonPath('summary.absent', 2);

        Carbon::setTestNow();
    }

    public function test_student_attendance_report_export_downloads_csv(): void
    {
        Carbon::setTestNow('2026-09-18 10:00:00');

        $branch = Branch::factory()->create(['library_open_time' => '09:00']);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $student = Student::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        $this->installAttendanceAddon();

        $this->actingAs($user)
            ->get(route('attendance.student-report.export', [
                'student' => $student,
                'date_from' => '2026-09-16',
                'date_to' => '2026-09-18',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        Carbon::setTestNow();
    }
}
