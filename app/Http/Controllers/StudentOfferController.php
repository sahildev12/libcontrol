<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendStudentOfferRequest;
use App\Models\Student;
use App\Services\MailDeliveryService;
use App\Services\StudentOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentOfferController extends Controller
{
    public function index(Request $request, StudentOfferService $offerService, MailDeliveryService $mailDelivery): View
    {
        $branchId = $this->optionalActiveBranchId($request);
        $viewingAll = $this->viewingAllBranches($request);
        $mailStatus = $mailDelivery->status();

        return view('offers.index', [
            'students' => $offerService->serializeStudentsForPicker($branchId),
            'offersEnabled' => $offerService->offersEnabled(),
            'mailConfigured' => $mailStatus['configured'],
            'mailIssue' => $mailStatus['message'],
            'viewingAll' => $viewingAll,
            'scopeLabel' => $viewingAll
                ? 'all branches'
                : ($this->optionalActiveBranch($request)?->name ?? ''),
            'emailSettingsUrl' => route('settings.index').'?tab=emails',
        ]);
    }

    public function send(SendStudentOfferRequest $request, StudentOfferService $offerService): JsonResponse
    {
        $branchId = $this->optionalActiveBranchId($request);
        $studentIds = $request->input('student_ids');

        if ($request->string('audience')->toString() === 'selected' && is_array($studentIds)) {
            $allowedIds = Student::query()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereIn('id', $studentIds)
                ->pluck('id')
                ->all();

            if (count($allowedIds) !== count($studentIds)) {
                abort(403, 'One or more selected students are outside your branch scope.');
            }
        }

        $result = $offerService->sendOffers(
            $branchId,
            $request->string('subject')->toString(),
            $request->string('message')->toString(),
            $request->string('audience')->toString(),
            is_array($studentIds) ? $studentIds : null,
            $request->input('action_url'),
        );

        if ($result['sent'] > 0) {
            $this->logActivity(
                $request,
                'offers.sent',
                "Sent offer email \"{$request->string('subject')}\" to {$result['sent']} student(s).",
                null,
                $branchId,
            );
        }

        $message = $result['sent'] === 1
            ? 'Offer email sent to 1 student.'
            : "Offer emails sent to {$result['sent']} students.";

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} could not be emailed.";
        }

        return response()->json([
            'message' => $message,
            'result' => $result,
        ]);
    }
}
