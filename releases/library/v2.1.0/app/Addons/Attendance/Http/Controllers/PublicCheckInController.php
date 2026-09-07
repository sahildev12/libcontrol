<?php

namespace App\Addons\Attendance\Http\Controllers;

use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Services\AttendanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCheckInController extends Controller
{
    public function show(string $token, AttendanceService $attendance): View
    {
        $settings = $attendance->settingsByToken($token);

        abort_unless($settings && $settings->student_qr_enabled, 404);

        $settings->load('branch:id,name,display_name');

        return view('attendance.check-in', [
            'token' => $token,
            'branchName' => $settings->branch?->display_name ?? $settings->branch?->name ?? 'Library',
        ]);
    }

    public function store(Request $request, string $token, AttendanceService $attendance): View
    {
        $settings = $attendance->settingsByToken($token);

        abort_unless($settings && $settings->student_qr_enabled, 404);

        $validated = $request->validate([
            'student_code' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $settings->load('branch');
        $branch = $settings->branch;
        abort_unless($branch, 404);

        $student = $attendance->verifyStudentCredentials(
            $branch,
            $validated['student_code'],
            $validated['phone'],
        );

        if (! $student) {
            return view('attendance.check-in-result', [
                'success' => false,
                'title' => 'Verification Failed',
                'message' => 'Student code and phone number do not match our records. Please check your details or contact library staff.',
                'branchName' => $branch->display_name ?? $branch->name,
            ]);
        }

        try {
            $record = $attendance->checkInStudent(
                $student,
                AttendanceRecord::METHOD_STUDENT_QR,
                meta: [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            );
        } catch (\Illuminate\Validation\ValidationException) {
            return view('attendance.check-in-result', [
                'success' => false,
                'title' => 'Already Checked In',
                'message' => 'Your attendance for today has already been recorded.',
                'branchName' => $branch->display_name ?? $branch->name,
            ]);
        }

        return view('attendance.check-in-result', [
            'success' => true,
            'title' => 'Check-in Successful',
            'message' => 'Welcome, '.$student->name.'! Your attendance was recorded at '.$record->check_in_at->format('h:i A').'.',
            'branchName' => $branch->display_name ?? $branch->name,
        ]);
    }
}
