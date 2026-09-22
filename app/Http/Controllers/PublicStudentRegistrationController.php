<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicStudentRegistrationRequest;
use App\Models\StudentRegistrationInvite;
use App\Services\StudentCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicStudentRegistrationController extends Controller
{
    public function show(string $token): View
    {
        $invite = StudentRegistrationInvite::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($invite->used_at) {
            $successName = session('registration_success_name');

            if (is_string($successName) && $successName !== '') {
                return $this->registrationStatus(
                    'Student Registered Successfully',
                    "Thank you, {$successName}! Your registration has been submitted to the library.",
                );
            }

            return $this->registrationStatus(
                'Link Already Used',
                'This registration link has already been used and is no longer available.',
            );
        }

        if ($invite->expires_at->isPast()) {
            return $this->registrationStatus(
                'Link Expired',
                'This registration link expired after 2 hours. Please ask the library staff for a new link.',
            );
        }

        $invite->load('branch:id,name,display_name');

        return view('students.register', [
            'invite' => $invite,
            'branchName' => $invite->branch?->display_name ?? $invite->branch?->name,
        ]);
    }

    public function store(
        StorePublicStudentRegistrationRequest $request,
        string $token,
        StudentCreator $studentCreator,
    ): View|RedirectResponse {
        $invite = StudentRegistrationInvite::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($invite->used_at !== null) {
            return $this->registrationStatus(
                'Link Already Used',
                'This registration link has already been used. If you already registered, please contact the library staff.',
            );
        }

        if ($invite->expires_at->isPast()) {
            return $this->registrationStatus(
                'Link Expired',
                'This registration link expired after 2 hours. Please ask the library staff for a new link.',
            );
        }

        $student = DB::transaction(function () use ($request, $token, $studentCreator) {
            $invite = StudentRegistrationInvite::query()
                ->where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($invite->used_at !== null) {
                return null;
            }

            if ($invite->expires_at->isPast()) {
                return null;
            }

            $invite->load('branch');

            $student = $studentCreator->create(
                $invite->branch,
                $request->safe()->except(['photo', 'id_proof']),
                $request->file('photo'),
                $request->file('id_proof'),
            );

            $invite->markUsed($student);

            return $student;
        });

        if ($student === null) {
            $invite->refresh();

            if ($invite->used_at !== null) {
                return $this->registrationStatus(
                    'Link Already Used',
                    'This registration link has already been used. If you already registered, please contact the library staff.',
                );
            }

            return $this->registrationStatus(
                'Link Expired',
                'This registration link expired after 2 hours. Please ask the library staff for a new link.',
            );
        }

        return redirect()
            ->route('students.register.show', $token)
            ->with('registration_success_name', $student->name);
    }

    private function registrationStatus(string $title, string $message): View
    {
        return view('students.register-status', [
            'title' => $title,
            'message' => $message,
        ]);
    }
}
