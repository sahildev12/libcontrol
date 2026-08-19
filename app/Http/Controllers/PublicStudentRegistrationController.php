<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicStudentRegistrationRequest;
use App\Models\StudentRegistrationInvite;
use App\Services\StudentCreator;
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
            return view('students.register-status', [
                'title' => 'Link Already Used',
                'message' => 'This registration link has already been used and is no longer available.',
            ]);
        }

        if ($invite->expires_at->isPast()) {
            return view('students.register-status', [
                'title' => 'Link Expired',
                'message' => 'This registration link expired after 2 hours. Please ask the library staff for a new link.',
            ]);
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
    ): View {
        $student = DB::transaction(function () use ($request, $token, $studentCreator) {
            $invite = StudentRegistrationInvite::query()
                ->where('token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($invite->used_at !== null, 410, 'This registration link has already been used.');
            abort_if($invite->expires_at->isPast(), 410, 'This registration link has expired.');

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

        return view('students.register-status', [
            'title' => 'Registration Complete',
            'message' => "Thank you, {$student->name}! Your details were submitted successfully.",
        ]);
    }
}
