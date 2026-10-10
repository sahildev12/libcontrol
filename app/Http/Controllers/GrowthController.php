<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GrowthOrder;
use App\Models\PlatformSetting;
use App\Models\Referral;
use App\Services\Growth\GrowthOrderService;
use App\Services\Growth\LibraryCombinedGrowthScoreService;
use App\Services\Growth\LibraryGrowthRecommendationService;
use App\Services\Growth\LibraryGrowthScoreService;
use App\Services\Growth\RazorpayGrowthBillingService;
use App\Services\Growth\ReferralService;
use App\Services\LibraryWebsiteService;
use App\Services\Profile\LibraryProfileCompletionService;
use App\Services\SupportTicketSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GrowthController extends Controller
{
    public function __construct()
    {
        abort_unless((bool) config('growth.enabled', true), 404);
    }

    public function index(
        Request $request,
        LibraryGrowthScoreService $scoreService,
        LibraryCombinedGrowthScoreService $combinedGrowthScore,
        LibraryProfileCompletionService $profileCompletion,
        GrowthOrderService $orderService,
        RazorpayGrowthBillingService $billing,
        ReferralService $referrals,
        LibraryWebsiteService $website,
        SupportTicketSyncService $ticketSync,
    ): View {
        $branch = $this->resolveGrowthBranch($request);
        $settings = PlatformSetting::current();
        $socialLinks = $website->settingsPayload($settings)['website_social_links'] ?? [];
        $combinedGrowth = $combinedGrowthScore->forBranch($branch, $request->user());
        $score = $combinedGrowth['action_progress'];
        $recommendations = $combinedGrowth['recommendations'];
        $profileCompletionScore = $profileCompletion->scoreForLibrary($request->user());
        $ticketSync->pullUpdates($request->user());
        $orders = GrowthOrder::query()
            ->with('requester:id,name')
            ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (GrowthOrder $order) => [
                'id' => $order->id,
                'item_name' => $order->item_name,
                'type_label' => $order->order_type === 'package' ? 'Package' : 'Service',
                'price_label' => config(($order->order_type === 'package' ? 'growth.packages.' : 'growth.services.').$order->item_key.'.price_label') ?? '—',
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'payment_status' => $order->payment_status,
                'requested_by' => $order->requester?->name ?? $order->contact_name,
                'created_at' => $order->created_at?->format('d M Y, h:i A'),
                'monthly_report_url' => $order->monthly_report_url,
                'team_note' => $order->admin_notes,
            ])
            ->values();

        $waPhone = preg_replace('/\D+/', '', (string) config('growth.whatsapp_phone', '8076105181')) ?: '918076105181';
        if (strlen($waPhone) === 10) {
            $waPhone = '91'.$waPhone;
        }

        return view('growth.index', [
            'branch' => $branch,
            'score' => $score,
            'combinedGrowth' => $combinedGrowth,
            'profileCompletionScore' => $profileCompletionScore,
            'recommendations' => $recommendations,
            'packages' => config('growth.packages', []),
            'services' => config('growth.services', []),
            'actions' => config('growth.actions', []),
            'orders' => $orders,
            'gstDisclaimer' => config('growth.gst_disclaimer'),
            'adSpendDisclaimer' => config('growth.ad_spend_disclaimer'),
            'razorpayEnabled' => $billing->enabled(),
            'whatsappBaseUrl' => $orderService->whatsappUrl('LibControl Growth'),
            'whatsappPhone' => $waPhone,
            'reviewTarget' => (int) config('growth.review_target', 50),
            'socialLinks' => is_array($socialLinks) ? $socialLinks : [],
            'referralSummary' => $referrals->summary($branch),
            'websiteUrl' => $settings->website_enabled ? route('home') : null,
            'libraryName' => $settings->display_name ?: $branch->name,
        ]);
    }

    public function updateProfile(Request $request, LibraryGrowthScoreService $scoreService): RedirectResponse
    {
        $branch = $this->resolveGrowthBranch($request);
        $profile = $scoreService->profileForBranch($branch);

        $request->merge(collect(['google_maps_url', 'google_review_url', 'facebook_url', 'instagram_url'])
            ->mapWithKeys(fn (string $key) => [$key => $this->normalizeUrl($request->input($key))])
            ->all());

        $anyTicked = collect(['gbp_claimed', 'gbp_photos', 'gbp_category', 'gbp_hours'])
            ->contains(fn (string $key) => $request->boolean($key));

        $validated = $request->validate([
            'gbp_claimed' => ['sometimes', 'boolean'],
            'gbp_photos' => ['sometimes', 'boolean'],
            'gbp_category' => ['sometimes', 'boolean'],
            'gbp_hours' => ['sometimes', 'boolean'],
            'google_maps_url' => [$anyTicked ? 'required' : 'nullable', 'string', 'max:500', 'url:http,https', 'regex:'.LibraryGrowthScoreService::GOOGLE_MAPS_PATTERN],
            'google_review_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'google_review_url' => ['nullable', 'string', 'max:500', 'url:http,https', 'regex:'.LibraryGrowthScoreService::GOOGLE_REVIEW_PATTERN],
            'facebook_url' => ['nullable', 'string', 'max:255', 'url:http,https', 'regex:'.LibraryWebsiteService::SOCIAL_LINKS['facebook']['pattern']],
            'instagram_url' => ['nullable', 'string', 'max:255', 'url:http,https', 'regex:'.LibraryWebsiteService::SOCIAL_LINKS['instagram']['pattern']],
        ], [
            'google_maps_url.required' => 'Add your Google Maps listing link before ticking the checklist.',
            'google_maps_url.url' => 'Enter a valid Google Maps link, e.g. https://maps.app.goo.gl/…',
            'google_maps_url.regex' => 'This must be a Google Maps link (maps.app.goo.gl, google.com/maps or g.page).',
            'google_review_url.url' => 'Enter a valid Google review link.',
            'google_review_url.regex' => 'This must be a Google review link, e.g. https://g.page/r/…/review',
            'facebook_url.url' => 'Enter a valid Facebook link, e.g. https://…',
            'facebook_url.regex' => 'This must be a Facebook link.',
            'instagram_url.url' => 'Enter a valid Instagram link, e.g. https://…',
            'instagram_url.regex' => 'This must be an Instagram link.',
        ]);

        $profile->forceFill([
            'gbp_claimed' => $request->boolean('gbp_claimed'),
            'gbp_photos' => $request->boolean('gbp_photos'),
            'gbp_category' => $request->boolean('gbp_category'),
            'gbp_hours' => $request->boolean('gbp_hours'),
            'google_maps_url' => $validated['google_maps_url'] ?? null,
            'google_review_count' => (int) ($validated['google_review_count'] ?? 0),
            'google_review_url' => $validated['google_review_url'] ?? null,
        ])->save();

        $settings = PlatformSetting::current();
        $links = is_array($settings->website_social_links) ? $settings->website_social_links : [];
        $links['facebook'] = $validated['facebook_url'] ?? null;
        $links['instagram'] = $validated['instagram_url'] ?? null;
        $settings->update(['website_social_links' => $links]);
        LibraryWebsiteService::syncSocialsToGrowthProfiles($settings->fresh());

        $scoreService->scoreForBranch($branch);

        return $this->backTo('visibility-form')->with('status', 'Visibility checklist saved. Facebook & Instagram links are synced with Settings → Website.');
    }

    private function normalizeUrl(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) ? $value : 'https://'.ltrim($value, '/');
    }

    public function requestPackage(
        Request $request,
        GrowthOrderService $orderService,
        RazorpayGrowthBillingService $billing,
    ): JsonResponse {
        return $this->requestItem($request, $orderService, $billing, 'package');
    }

    public function requestService(
        Request $request,
        GrowthOrderService $orderService,
        RazorpayGrowthBillingService $billing,
        LibraryGrowthScoreService $scoreService,
        LibraryGrowthRecommendationService $recommendations,
    ): JsonResponse {
        if ($request->input('source') === 'recommendation') {
            $profile = $scoreService->profileForBranch($this->resolveGrowthBranch($request));
            if (! $recommendations->hasActivePackage($profile)) {
                return response()->json([
                    'message' => 'Hire an expert is available with an active Grow My Library package.',
                    'packages_url' => route('growth.index').'#packages',
                ], 403);
            }
        }

        return $this->requestItem($request, $orderService, $billing, 'service');
    }

    public function startReferralCampaign(Request $request, LibraryGrowthScoreService $scoreService): RedirectResponse
    {
        $branch = $this->resolveGrowthBranch($request);
        $validated = $request->validate([
            'referral_offer_text' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $profile = $scoreService->profileForBranch($branch);
        $wasActive = (bool) $profile->referral_campaign_active;
        $profile->forceFill([
            'referral_campaign_active' => true,
            'referral_offer_text' => trim($validated['referral_offer_text']),
        ])->save();
        $scoreService->scoreForBranch($branch);

        return $this->backTo('referral-kit')->with('status', $wasActive
            ? 'Referral offer updated.'
            : 'Referral campaign is live. Share the message with your students — friends enter their Student ID when they enquire or join.');
    }

    public function stopReferralCampaign(Request $request, LibraryGrowthScoreService $scoreService): RedirectResponse
    {
        $branch = $this->resolveGrowthBranch($request);
        $scoreService->profileForBranch($branch)->forceFill(['referral_campaign_active' => false])->save();
        $scoreService->scoreForBranch($branch);

        return $this->backTo('referral-kit')->with('status', 'Referral campaign paused. Existing referrals and rewards are kept.');
    }

    public function markReferralRewardGiven(Request $request, Referral $referral): RedirectResponse
    {
        $this->assertCanAccessBranch($request, $referral->branch_id);
        abort_unless($referral->reward_status === Referral::REWARD_DUE, 422, 'This reward is not due.');

        $referral->update([
            'reward_status' => Referral::REWARD_GIVEN,
            'reward_given_at' => now(),
        ]);

        $referrer = $referral->referrer;
        $this->logActivity(
            $request,
            'referral.reward_given',
            'Gave referral reward to '.($referrer?->name ?? 'student').' ('.($referrer?->student_code ?? '—').") for referring {$referral->referred_name}.",
            $referral,
            $referral->branch_id,
        );

        return $this->backTo('referrals')->with('status', 'Reward marked as given to '.($referrer?->name ?? 'the student').'.');
    }

    private function backTo(string $anchor): RedirectResponse
    {
        return redirect()->to(strtok(url()->previous(), '#').'#'.$anchor);
    }

    private function requestItem(
        Request $request,
        GrowthOrderService $orderService,
        RazorpayGrowthBillingService $billing,
        string $type,
    ): JsonResponse {
        $validated = $request->validate([
            'item_key' => ['required', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:2000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'with_checkout' => ['sometimes', 'boolean'],
        ]);

        $branch = $this->resolveGrowthBranch($request);
        $result = $orderService->request($request->user(), $branch, [
            'order_type' => $type,
            'item_key' => $validated['item_key'],
            'message' => $validated['message'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
        ]);

        $checkout = null;
        if ($request->boolean('with_checkout') && $billing->enabled()) {
            $checkout = $billing->checkoutUrlForOrder($result['order']);
        }

        return response()->json([
            'message' => ($result['ticket_synced'] || $result['email_sent'])
                ? 'Request sent to Phenomit. Our team will contact you shortly.'
                : 'Request saved. '.($result['ticket_error'] ?: 'Use WhatsApp if you need a faster reply.'),
            'order' => [
                'uuid' => $result['order']->uuid,
                'item_name' => $result['order']->item_name,
                'status' => $result['order']->status,
            ],
            'whatsapp_url' => $result['whatsapp_url'],
            'checkout_url' => $checkout['url'] ?? null,
            'checkout_error' => $checkout['error'] ?? null,
        ], 201);
    }

    private function resolveGrowthBranch(Request $request): Branch
    {
        $branch = $this->optionalActiveBranch($request);
        if ($branch) {
            return $branch;
        }

        if ($request->user()?->branch_id) {
            return Branch::query()->findOrFail((int) $request->user()->branch_id);
        }

        $first = Branch::query()->orderBy('name')->first();
        abort_unless($first, 422, 'Create a branch before using Grow My Library.');

        return $first;
    }
}
