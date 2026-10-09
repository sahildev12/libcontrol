<?php

namespace App\Services\Growth;

use App\Models\LibraryGrowthProfile;

class LibraryGrowthRecommendationService
{
    public function hasActivePackage(?LibraryGrowthProfile $profile): bool
    {
        return $profile !== null
            && filled($profile->active_package)
            && ($profile->package_active_until === null || $profile->package_active_until->isFuture());
    }

    /**
     * @param  array{items?: list<array{key: string, label: string, status: string, detail: string, points: int, max_points: int}>, profile?: LibraryGrowthProfile|null}  $actionProgress
     * @return array{
     *     has_package: bool,
     *     free_limit: int,
     *     unlocked: list<array<string, mixed>>,
     *     locked: list<array{key: string, gain: int}>,
     *     locked_gain: int,
     *     completed: list<array{key: string, title: string}>,
     *     completed_count: int,
     *     total: int
     * }
     */
    public function fromActionProgress(array $actionProgress): array
    {
        $config = config('growth.recommendations', []);
        $catalog = $config['items'] ?? [];
        $effortOrder = $config['effort_order'] ?? ['easy' => 0, 'medium' => 1, 'hard' => 2];
        $freeLimit = max(0, (int) ($config['free_limit'] ?? 3));
        $hasPackage = $this->hasActivePackage($actionProgress['profile'] ?? null);

        $items = $actionProgress['items'] ?? [];
        $open = [];
        $completed = [];

        foreach ($items as $index => $item) {
            $key = (string) ($item['key'] ?? '');
            $meta = $catalog[$key] ?? [];
            $title = (string) ($meta['title'] ?? $item['label'] ?? $key);

            if (($item['status'] ?? '') === 'ok') {
                $completed[] = ['key' => $key, 'title' => $title];

                continue;
            }

            $effort = (string) ($meta['effort'] ?? 'medium');
            $open[] = [
                'key' => $key,
                'title' => $title,
                'label' => (string) ($item['label'] ?? $key),
                'detail' => (string) ($item['detail'] ?? ''),
                'why' => (string) ($meta['why'] ?? ''),
                'effort' => $effort,
                'effort_rank' => (int) ($effortOrder[$effort] ?? 1),
                'diy_steps' => array_values($meta['diy_steps'] ?? []),
                'expert_service' => $meta['expert_service'] ?? null,
                'gain' => max(0, (int) ($item['max_points'] ?? 0) - (int) ($item['points'] ?? 0)),
                'order' => $index,
            ];
        }

        usort($open, fn (array $a, array $b) => [$a['effort_rank'], -$a['gain'], $a['order']]
            <=> [$b['effort_rank'], -$b['gain'], $b['order']]);

        $unlocked = [];
        $locked = [];

        foreach ($open as $position => $rec) {
            unset($rec['effort_rank'], $rec['order']);

            if ($hasPackage) {
                $rec['expert_available'] = filled($rec['expert_service'] ?? null);
                $unlocked[] = $rec;

                continue;
            }

            if ($position < $freeLimit) {
                $rec['expert_available'] = filled($rec['expert_service'] ?? null);
                $rec['expert_service'] = null;
                $unlocked[] = $rec;

                continue;
            }

            $locked[] = ['key' => $rec['key'], 'gain' => $rec['gain']];
        }

        return [
            'has_package' => $hasPackage,
            'free_limit' => $freeLimit,
            'unlocked' => $unlocked,
            'locked' => $locked,
            'locked_gain' => (int) collect($locked)->sum('gain'),
            'completed' => $completed,
            'completed_count' => count($completed),
            'total' => count($items),
        ];
    }
}
