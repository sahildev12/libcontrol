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

    /**
     * Keep Edwik visible before the library has a branch, with one simple next step.
     *
     * @return array{
     *     growth_score: int,
     *     action_progress: null,
     *     business_performance: null,
     *     recommendations: array,
     *     action_pct: int,
     *     business_pct: int,
     *     formula: array{action_weight: float, business_weight: float},
     *     bootstrap: bool
     * }
     */
    public function forEmptyLibrary(): array
    {
        $createBranchUrl = \Illuminate\Support\Facades\Route::has('branch.index')
            ? route('branch.index')
            : '#';

        $catalog = config('growth.recommendations.items', []);
        $locked = [];
        foreach ($catalog as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $locked[] = [
                'key' => (string) $key,
                'title' => (string) ($item['title'] ?? 'Growth tip'),
                'why' => (string) ($item['why'] ?? 'Create a branch to unlock this tip.'),
                'effort' => (string) ($item['effort'] ?? 'medium'),
                'gain' => 0,
            ];
        }

        return [
            'growth_score' => 0,
            'action_progress' => null,
            'business_performance' => null,
            'recommendations' => [
                'has_package' => true,
                'free_limit' => 3,
                'unlocked' => [[
                    'key' => 'create_branch',
                    'title' => 'Create your first branch',
                    'label' => 'Create your first branch',
                    'why' => 'Add a branch to unlock halls, seats, students, and more AI growth tips for your library.',
                    'detail' => 'Add a branch to unlock halls, seats, students, and more AI growth tips for your library.',
                    'effort' => 'easy',
                    'gain' => 0,
                    'cta_label' => 'Create Branch',
                    'cta_url' => $createBranchUrl,
                    'expert_service' => null,
                    'expert_available' => false,
                ]],
                'locked' => $locked,
                'locked_gain' => 0,
                'completed' => [],
                'completed_count' => 0,
                'total' => 1 + count($locked),
            ],
            'action_pct' => 0,
            'business_pct' => 0,
            'formula' => [
                'action_weight' => (float) config('growth.growth_score.action_weight', 0.40),
                'business_weight' => (float) config('growth.growth_score.business_weight', 0.60),
            ],
            'bootstrap' => true,
        ];
    }
}
