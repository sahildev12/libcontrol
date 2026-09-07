<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAuthApiController extends Controller
{
    public function __construct(
        private StudentAuthService $studentAuth,
    ) {}

    public function checkCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_code' => ['required', 'string', 'max:50'],
        ]);

        $student = $this->studentAuth->findActiveStudentByCode($validated['student_code']);
        $needsPinSetup = ! $student->hasAppPin();

        $payload = [
            'needs_pin_setup' => $needsPinSetup,
            'student' => $this->studentAuth->serializeLookup($student),
        ];

        if ($needsPinSetup) {
            $payload['setup_token'] = $this->studentAuth->issuePinSetupToken($student);
        }

        return response()->json($payload);
    }

    public function setupPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setup_token' => ['required', 'string', 'size:64'],
            'pin' => ['required', 'string', 'digits_between:4,6'],
            'pin_confirmation' => ['required', 'same:pin'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $student = $this->studentAuth->completePinSetup(
            $validated['setup_token'],
            $validated['pin'],
        );

        $token = $student->createToken($validated['device_name'] ?? 'student-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'student' => $this->studentAuth->serializeProfile($student),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_code' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'string', 'digits_between:4,6'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $student = $this->studentAuth->authenticate(
            $validated['student_code'],
            $validated['pin'],
        );

        $token = $student->createToken($validated['device_name'] ?? 'student-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'student' => $this->studentAuth->serializeProfile($student),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        return response()->json([
            'student' => $this->studentAuth->serializeProfile($student),
        ]);
    }
}
