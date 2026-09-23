<?php

namespace App\Addons\Attendance\Http\Controllers;

use App\Addons\Attendance\Http\Requests\PublicAttendanceCheckInRequest;
use App\Addons\Attendance\Models\AttendanceRecord;
use App\Addons\Attendance\Services\AttendanceService;
use App\Http\Controllers\Controller;
use App\Services\BranchStudentCodePrefixService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicCheckInController extends Controller
{
    public function show(string $token, AttendanceService $attendance, BranchStudentCodePrefixService $codes): View
    {
        $settings = $attendance->settingsByToken($token);

        abort_unless($settings && $settings->student_qr_enabled, 404);

        $settings->load('branch:id,name,display_name,student_code_prefix,student_code_padding');
        $branch = $settings->branch;
        abort_unless($branch, 404);

        $prefix = $codes->resolvedPrefix($branch);

        return view('attendance.check-in', [
            'token' => $token,
            'branchName' => $branch->display_name ?? $branch->name ?? 'Library',
            'studentCodePrefix' => $prefix,
            'studentCodeSample' => $codes->sampleCode($branch),
        ]);
    }

    public function store(
        PublicAttendanceCheckInRequest $request,
        string $token,
        AttendanceService $attendance,
        BranchStudentCodePrefixService $codes,
    ): View {
        $settings = $attendance->settingsByToken($token);

        abort_unless($settings && $settings->student_qr_enabled, 404);

        $validated = $request->validated();

        $settings->load('branch');
        $branch = $settings->branch;
        abort_unless($branch, 404);

        $studentCode = $codes->formatStudentCode($branch, $validated['student_code_suffix']);

        $student = $attendance->verifyStudentCredentials(
            $branch,
            $studentCode,
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

        $meta = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $action = $validated['action'];

        try {
            if ($action === 'check_out') {
                $record = $attendance->checkOutStudent($student, $meta);

                return view('attendance.check-in-result', [
                    'success' => true,
                    'title' => 'Check-out Successful',
                    'message' => 'Goodbye, '.$student->name.'! You checked out at '.$record->check_out_at->format('h:i A').'.',
                    'branchName' => $branch->display_name ?? $branch->name,
                ]);
            }

            $record = $attendance->checkInStudent(
                $student,
                AttendanceRecord::METHOD_STUDENT_QR,
                meta: $meta,
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first()
                ?? 'Unable to record attendance.';

            return view('attendance.check-in-result', [
                'success' => false,
                'title' => $action === 'check_out' ? 'Check-out Failed' : 'Already Checked In',
                'message' => $message,
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
