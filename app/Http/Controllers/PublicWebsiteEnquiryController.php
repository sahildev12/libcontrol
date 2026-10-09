<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\PlatformSetting;
use App\Services\Growth\ReferralService;
use App\Support\ValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicWebsiteEnquiryController extends Controller
{
    public function store(Request $request, ReferralService $referrals): JsonResponse
    {
        abort_unless(config('libcontrol.modules.enquiries'), 404);
        abort_unless(PlatformSetting::current()->website_enabled, 404);
        abort_unless(Schema::hasTable('enquiries'), 503);

        $branch = Branch::query()->orderBy('id')->first();
        abort_unless($branch, 422, 'No branch is configured yet.');

        $referralOpen = $referrals->campaignActive($branch->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80', "regex:/^\\pL[\\pL\\s.'-]*$/u"],
            'phone' => ValidationRules::phoneRequired(),
            'email' => ['nullable', 'string', 'max:255', 'email:rfc,filter'],
            'message' => ['nullable', 'string', 'max:1000'],
            'referred_by' => $referralOpen
                ? ['nullable', 'string', 'max:40', $referrals->codeRule((string) $request->input('phone'))]
                : ['nullable'],
            'website' => ['prohibited'],
        ], [
            'name.regex' => 'Name can only contain letters, spaces, dots, hyphens and apostrophes.',
            'phone.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'email.email' => 'Enter a valid email address.',
        ]);

        $enquiry = Enquiry::query()->create([
            'branch_id' => $branch->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'new',
        ]);

        if ($referralOpen && filled($validated['referred_by'] ?? null)) {
            $referrer = $referrals->findReferrer($validated['referred_by']);
            if ($referrer) {
                $referrals->recordFromEnquiry($enquiry, $referrer);
            }
        }

        return response()->json([
            'message' => 'Thank you! We received your enquiry and will contact you soon.',
            'id' => $enquiry->id,
        ], 201);
    }
}
