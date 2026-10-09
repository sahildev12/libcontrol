<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\GrowthOrder;
use App\Models\LibraryGrowthProfile;
use App\Models\PlatformSetting;
use App\Services\Growth\LibraryGrowthRecommendationService;
use App\Services\Growth\LibraryGrowthScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ResetGrowthDemo extends Command
{
    protected $signature = 'growth:demo-reset
                            {--branch= : Branch ID (default: first branch)}
                            {--with-package= : Simulate an active package: starter, grow, or dominate}
                            {--force : Required on production}';

    protected $description = 'Reset growth checklist data so you can preview the new-client recommendations flow';

    public function handle(
        LibraryGrowthScoreService $scores,
        LibraryGrowthRecommendationService $recommendations,
    ): int {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Add --force to reset growth demo data on production.');

            return self::FAILURE;
        }

        $branchId = $this->option('branch');
        $branch = $branchId
            ? Branch::query()->find($branchId)
            : Branch::query()->first();

        if (! $branch) {
            $this->error('No branch found.');

            return self::FAILURE;
        }

        $settings = PlatformSetting::current();
        $settings->forceFill([
            'website_enabled' => false,
            'website_tagline' => null,
            'website_whatsapp' => null,
            'website_social_links' => [],
        ])->save();

        $package = $this->option('with-package');
        $packageFields = [
            'gbp_claimed' => false,
            'gbp_photos' => false,
            'gbp_category' => false,
            'gbp_hours' => false,
            'google_maps_url' => null,
            'google_review_count' => 0,
            'facebook_url' => null,
            'instagram_url' => null,
            'last_whatsapp_campaign_at' => null,
            'referral_campaign_active' => false,
            'referral_offer_text' => null,
            'active_package' => null,
            'package_active_until' => null,
        ];

        if (filled($package) && in_array($package, ['starter', 'grow', 'dominate'], true)) {
            $packageFields['active_package'] = $package;
            $packageFields['package_active_until'] = now()->addMonth();
        }

        LibraryGrowthProfile::query()->updateOrCreate(
            ['branch_id' => $branch->id],
            $packageFields
        );

        if (Schema::hasTable('enquiries')) {
            Enquiry::query()->where('branch_id', $branch->id)->delete();
        }

        if (Schema::hasTable('growth_orders')) {
            GrowthOrder::query()->where('branch_id', $branch->id)->delete();
        }

        $score = $scores->scoreForBranch($branch);
        $recs = $recommendations->fromActionProgress($score);

        $this->info("Branch: {$branch->name} (#{$branch->id})");
        $this->line("Completed: {$recs['completed_count']}/{$recs['total']}");
        $this->line('Unlocked: '.count($recs['unlocked']).' · Locked: '.count($recs['locked']));
        $this->line('Package active: '.($recs['has_package'] ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
