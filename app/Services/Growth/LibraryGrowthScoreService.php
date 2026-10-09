<?php

namespace App\Services\Growth;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\GrowthOrder;
use App\Models\LibraryGrowthProfile;
use App\Models\PlatformSetting;
use App\Services\LibraryWebsiteService;
use Illuminate\Support\Facades\Schema;

class LibraryGrowthScoreService
{
    public const GOOGLE_MAPS_PATTERN = '#^https?://((www\.)?google\.[a-z.]+/maps|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl/maps|g\.page|g\.co/kgs|share\.google)(/\S*)?$#i';

    public const GOOGLE_REVIEW_PATTERN = '#^https?://(g\.page/r/|search\.google\.com/local/writereview|maps\.app\.goo\.gl/|(www\.)?google\.[a-z.]+/maps|g\.co/kgs/|share\.google/)\S*$#i';

    public function __construct(
        private LibraryWebsiteService $website,
        private ReferralService $referrals,
    ) {}

    public static function isGoogleMapsUrl(?string $url): bool
    {
        return filled($url) && preg_match(self::GOOGLE_MAPS_PATTERN, (string) $url) === 1;
    }

    public function profileForBranch(Branch $branch): LibraryGrowthProfile
    {
        return LibraryGrowthProfile::query()->firstOrCreate(
            ['branch_id' => $branch->id],
            ['score_cached' => 0]
        );
    }

    /**
     * @return array{
     *     score: int,
     *     max: int,
     *     items: list<array{key: string, label: string, status: string, detail: string, points: int, max_points: int}>,
     *     profile: LibraryGrowthProfile
     * }
     */
    public function scoreForBranch(Branch $branch, bool $persist = true): array
    {
        $profile = $this->profileForBranch($branch);
        $settings = PlatformSetting::current();
        $website = $this->website->settingsPayload($settings);
        $reviewTarget = max(1, (int) config('growth.review_target', 50));

        $items = [];

        $websiteComplete = (bool) ($website['website_enabled'] ?? false)
            && filled($website['website_whatsapp'] ?? null)
            && filled($website['website_tagline'] ?? null);
        $items[] = $this->item(
            'website',
            'Website',
            $websiteComplete ? 'ok' : 'missing',
            $websiteComplete ? 'Public website is enabled with WhatsApp + tagline.' : 'Enable website and add WhatsApp + tagline in Settings → Website.',
            $websiteComplete ? 15 : 0,
            15
        );

        $enquiriesEnabled = (bool) config('libcontrol.modules.enquiries');
        $recentEnquiries = 0;
        if ($enquiriesEnabled && Schema::hasTable('enquiries')) {
            $recentEnquiries = Enquiry::query()
                ->where('branch_id', $branch->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        }
        $enquiryStatus = ! $enquiriesEnabled
            ? 'missing'
            : ($recentEnquiries > 0 ? 'ok' : 'warn');
        $items[] = $this->item(
            'enquiries',
            'Enquiries pipeline',
            $enquiryStatus,
            ! $enquiriesEnabled
                ? 'Turn on LIBCONTROL_ENQUIRIES_ENABLED to capture leads.'
                : ($recentEnquiries > 0
                    ? "{$recentEnquiries} enquir".($recentEnquiries === 1 ? 'y' : 'ies').' in the last 30 days.'
                    : 'No enquiries in the last 30 days — promote your Enquire form.'),
            $enquiryStatus === 'ok' ? 15 : ($enquiryStatus === 'warn' ? 7 : 0),
            15
        );

        $hasMapsLink = self::isGoogleMapsUrl($profile->google_maps_url);
        $gbpDone = $hasMapsLink ? collect([
            $profile->gbp_claimed,
            $profile->gbp_photos,
            $profile->gbp_category,
            $profile->gbp_hours,
        ])->filter()->count() : 0;
        $gbpStatus = $gbpDone === 4 ? 'ok' : ($gbpDone > 0 ? 'warn' : 'missing');
        $items[] = $this->item(
            'gbp',
            'Google Profile',
            $gbpStatus,
            ! $hasMapsLink
                ? 'Add your Google Maps listing link to count the Google Business Profile checklist.'
                : ($gbpDone === 4
                    ? 'Google Business Profile checklist complete.'
                    : "{$gbpDone}/4 checklist items done (claimed, photos, category, hours)."),
            (int) round(($gbpDone / 4) * 15),
            15
        );

        $reviews = (int) $profile->google_review_count;
        $reviewStatus = $reviews >= $reviewTarget ? 'ok' : ($reviews > 0 ? 'warn' : 'missing');
        $items[] = $this->item(
            'reviews',
            'Google Reviews',
            $reviewStatus,
            "{$reviews} → need {$reviewTarget}+",
            min(15, (int) round(($reviews / $reviewTarget) * 15)),
            15
        );

        $socialLinks = is_array($website['website_social_links'] ?? null)
            ? $website['website_social_links']
            : [];
        $ig = filled($socialLinks['instagram'] ?? null) || filled($profile->instagram_url);
        $fb = filled($socialLinks['facebook'] ?? null) || filled($profile->facebook_url);
        $socialStatus = ($ig && $fb) ? 'ok' : (($ig || $fb) ? 'warn' : 'missing');
        $items[] = $this->item(
            'instagram',
            'Instagram',
            $ig ? 'ok' : 'missing',
            $ig ? 'Instagram URL saved.' : 'Add Instagram profile URL in Grow My Library or Settings → Website.',
            $ig ? 5 : 0,
            5
        );
        $items[] = $this->item(
            'facebook',
            'Facebook',
            $fb ? 'ok' : 'missing',
            $fb ? 'Facebook URL saved.' : 'Add Facebook page URL in Grow My Library or Settings → Website.',
            $fb ? 5 : 0,
            5
        );

        $waRecent = $profile->last_whatsapp_campaign_at?->gt(now()->subDays(45));
        $items[] = $this->item(
            'whatsapp',
            'Promotion campaign',
            $waRecent ? 'ok' : 'missing',
            $waRecent
                ? 'Promotion sent to students within the last 45 days.'
                : 'Send an offer from the Promotion page or request Phenomit WhatsApp Marketing.',
            $waRecent ? 10 : 0,
            10
        );

        $hasPackage = filled($profile->active_package)
            && ($profile->package_active_until === null || $profile->package_active_until->isFuture());
        $packageRequested = ! $hasPackage && Schema::hasTable('growth_orders')
            && GrowthOrder::query()
                ->where('branch_id', $branch->id)
                ->where('order_type', 'package')
                ->whereIn('status', [
                    GrowthOrder::STATUS_NEW,
                    GrowthOrder::STATUS_QUOTED,
                    GrowthOrder::STATUS_ACTIVE,
                ])
                ->exists();
        $localSeoOk = $hasPackage || $packageRequested;
        $items[] = $this->item(
            'local_seo',
            'Local SEO / Ads package',
            $localSeoOk ? 'ok' : 'missing',
            $hasPackage
                ? 'Active growth package: '.ucfirst((string) $profile->active_package).'.'
                : ($packageRequested
                    ? 'Managed package requested — Phenomit will activate it shortly.'
                    : 'No active managed package — request Starter, Grow, or Dominate.'),
            $localSeoOk ? 10 : 0,
            10
        );

        $referralActive = (bool) $profile->referral_campaign_active;
        $referralsJoined = $this->referrals->joinedRecently((int) $branch->id);
        $referralStatus = $referralsJoined > 0 ? 'ok' : ($referralActive ? 'warn' : 'missing');
        $items[] = $this->item(
            'referral',
            'Referral Campaign',
            $referralStatus,
            match ($referralStatus) {
                'ok' => "{$referralsJoined} referred student".($referralsJoined === 1 ? '' : 's').' joined in the last 90 days.',
                'warn' => 'Referral offer active — no referred student has joined yet.',
                default => 'Start a referral offer from Grow My Library.',
            },
            match ($referralStatus) {
                'ok' => 10,
                'warn' => 4,
                default => 0,
            },
            10
        );

        $score = (int) collect($items)->sum('points');
        $max = (int) collect($items)->sum('max_points');

        if ($persist) {
            $profile->forceFill([
                'score_cached' => $score,
                'scored_at' => now(),
            ])->save();
        }

        return [
            'score' => $score,
            'max' => $max,
            'items' => $items,
            'profile' => $profile->fresh(),
            'social_status' => $socialStatus,
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string, points: int, max_points: int}
     */
    private function item(string $key, string $label, string $status, string $detail, int $points, int $maxPoints): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'detail' => $detail,
            'points' => $points,
            'max_points' => $maxPoints,
        ];
    }
}
