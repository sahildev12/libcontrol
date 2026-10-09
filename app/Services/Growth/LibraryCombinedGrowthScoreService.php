<?php

namespace App\Services\Growth;

use App\Models\Branch;
use App\Models\User;

class LibraryCombinedGrowthScoreService
{
    public function __construct(
        private LibraryGrowthScoreService $actionProgress,
        private LibraryBusinessPerformanceService $businessPerformance,
        private LibraryGrowthRecommendationService $recommendations,
    ) {}

    /**
     * @return array{
     *     growth_score: int,
     *     action_progress: array,
     *     business_performance: array,
     *     recommendations: array,
     *     action_pct: int,
     *     business_pct: int,
     *     formula: array{action_weight: float, business_weight: float}
     * }
     */
    public function forBranch(Branch $branch, ?User $user = null): array
    {
        $action = $this->actionProgress->scoreForBranch($branch);
        $business = $this->businessPerformance->scoreForLibrary($user, $branch->id);

        $actionWeight = (float) config('growth.growth_score.action_weight', 0.40);
        $businessWeight = (float) config('growth.growth_score.business_weight', 0.60);

        $actionMax = max(1, (int) ($action['max'] ?? 100));
        $actionPct = (int) round(min(100, max(0, ((int) ($action['score'] ?? 0) / $actionMax) * 100)));
        $businessPct = (int) ($business['score_rounded'] ?? 0);

        $growth = (int) round(($actionPct * $actionWeight) + ($businessPct * $businessWeight));

        return [
            'growth_score' => max(0, min(100, $growth)),
            'action_progress' => $action,
            'business_performance' => $business,
            'recommendations' => $this->recommendations->fromActionProgress($action),
            'action_pct' => $actionPct,
            'business_pct' => $businessPct,
            'formula' => [
                'action_weight' => $actionWeight,
                'business_weight' => $businessWeight,
            ],
        ];
    }
}
