<?php

namespace App\Services\Growth;

use App\Models\Enquiry;
use App\Models\GrowthOrder;
use App\Models\LibraryGrowthProfile;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class LibraryGrowthRecommendationService
{
    public function hasActivePackage(?LibraryGrowthProfile $profile): bool
    {
        if ($profile !== null
            && filled($profile->active_package)
            && ($profile->package_active_until === null || $profile->package_active_until->isFuture())) {
            return true;
        }

        // Seeded / manually activated orders sometimes skip profile sync — heal from growth_orders.
        if ($this->syncActivePackageFromOrders($profile)) {
            return true;
        }

        // Paid LibControl (non-trial) tenants should not see the “Upgrade to Premium” growth upsell.
        return $this->hasPaidLibControlPlan();
    }

    public function hasPaidLibControlPlan(): bool
    {
        $tenant = TenantContext::current();

        // Only trust tenancy context — never invent “paid” on landlord / local test runs.
        return $tenant !== null && ! (bool) $tenant->is_trial;
    }

    /**
     * If a package order is already active but the growth profile was never updated, sync it.
     */
    private function syncActivePackageFromOrders(?LibraryGrowthProfile $profile): bool
    {
        if (! Schema::hasTable('growth_orders')) {
            return false;
        }

        $orderQuery = GrowthOrder::query()
            ->where('order_type', 'package')
            ->where('status', GrowthOrder::STATUS_ACTIVE)
            ->orderByDesc('id');

        $order = (clone $orderQuery)
            ->when($profile?->branch_id, fn ($q) => $q->where('branch_id', $profile->branch_id))
            ->first();

        // Library-wide fallback (e.g. viewing all branches).
        $order ??= $orderQuery->first();

        if ($order === null) {
            return false;
        }

        $package = config('growth.packages.'.$order->item_key);
        if (is_array($package) && ! empty($package['skip_profile_activation'])) {
            return false;
        }

        $target = $profile;
        if ($target === null && $order->branch_id) {
            $target = LibraryGrowthProfile::query()->firstOrCreate(
                ['branch_id' => $order->branch_id],
                ['score_cached' => 0]
            );
        }

        if ($target !== null) {
            $until = $target->package_active_until;
            $target->forceFill([
                'active_package' => $order->item_key,
                'package_active_until' => ($until !== null && $until->isFuture()) ? $until : now()->addMonth(),
            ])->save();
        }

        return true;
    }

    /**
     * @param  array{items?: list<array{key: string, label: string, status: string, detail: string, points: int, max_points: int}>, profile?: LibraryGrowthProfile|null}  $actionProgress
     * @param  array{factors?: list<array{key: string, score: float|null, available: bool, detail?: string}>, score_rounded?: int}|null  $businessPerformance
     * @return array{
     *     has_package: bool,
     *     free_limit: int,
     *     pro_banner_limit: int,
     *     unlocked: list<array<string, mixed>>,
     *     locked: list<array{key: string, gain: int}>,
     *     locked_gain: int,
     *     completed: list<array{key: string, title: string}>,
     *     completed_count: int,
     *     total: int
     * }
     */
    public function fromActionProgress(array $actionProgress, ?array $businessPerformance = null): array
    {
        $config = config('growth.recommendations', []);
        $catalog = $config['items'] ?? [];
        $effortOrder = $config['effort_order'] ?? ['easy' => 0, 'medium' => 1, 'hard' => 2];
        $freeLimit = max(0, (int) ($config['free_limit'] ?? 3));
        $proBannerLimit = max($freeLimit, (int) ($config['pro_banner_limit'] ?? 8));
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
            $cta = $this->resolveCta($meta);
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
                'cta_label' => $cta['label'],
                'cta_url' => $cta['url'],
                'order' => $index,
                'source' => 'action',
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

        if ($hasPackage && is_array($businessPerformance)) {
            [$proUnlocked, $proCompleted] = $this->proTipsFromBusiness($businessPerformance, $effortOrder);
            $unlocked = array_values(array_merge($proUnlocked, $unlocked));
            $completed = array_values(array_merge($completed, $proCompleted));
        }

        return [
            'has_package' => $hasPackage,
            'free_limit' => $freeLimit,
            'pro_banner_limit' => $proBannerLimit,
            'unlocked' => $unlocked,
            'locked' => $locked,
            'locked_gain' => (int) collect($locked)->sum('gain'),
            'completed' => $completed,
            'completed_count' => count($completed),
            'total' => count($items) + count(config('growth.recommendations.pro_items', [])),
        ];
    }

    /**
     * @param  array{factors?: list<array{key: string, score: float|null, available: bool, detail?: string}>}  $businessPerformance
     * @param  array<string, int>  $effortOrder
     * @return array{0: list<array<string, mixed>>, 1: list<array{key: string, title: string}>}
     */
    private function proTipsFromBusiness(array $businessPerformance, array $effortOrder): array
    {
        $catalog = config('growth.recommendations.pro_items', []);
        $factors = collect($businessPerformance['factors'] ?? [])->keyBy('key');
        $unlocked = [];
        $completed = [];
        $order = 0;

        foreach ($catalog as $key => $meta) {
            if (! is_array($meta)) {
                continue;
            }

            $key = (string) $key;
            $title = (string) ($meta['title'] ?? $key);
            $done = $this->proTipIsDone($key, $meta, $factors);

            if ($done) {
                $completed[] = ['key' => $key, 'title' => $title];

                continue;
            }

            $effort = (string) ($meta['effort'] ?? 'medium');
            $cta = $this->resolveCta($meta);
            $unlocked[] = [
                'key' => $key,
                'title' => $title,
                'label' => $title,
                'detail' => (string) ($factors->get($meta['factor'] ?? '')['detail'] ?? ''),
                'why' => (string) ($meta['why'] ?? ''),
                'effort' => $effort,
                'effort_rank' => (int) ($effortOrder[$effort] ?? 1),
                'diy_steps' => array_values($meta['diy_steps'] ?? []),
                'expert_service' => $meta['expert_service'] ?? null,
                'expert_available' => filled($meta['expert_service'] ?? null),
                'gain' => max(0, (int) ($meta['gain'] ?? 0)),
                'cta_label' => $cta['label'],
                'cta_url' => $cta['url'],
                'order' => $order++,
                'source' => 'pro',
            ];
        }

        usort($unlocked, fn (array $a, array $b) => [$a['effort_rank'], -$a['gain'], $a['order']]
            <=> [$b['effort_rank'], -$b['gain'], $b['order']]);

        foreach ($unlocked as &$rec) {
            unset($rec['effort_rank'], $rec['order']);
        }
        unset($rec);

        return [$unlocked, $completed];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  \Illuminate\Support\Collection<string, array{key: string, score: float|null, available: bool}>  $factors
     */
    private function proTipIsDone(string $key, array $meta, $factors): bool
    {
        if ($key === 'clear_enquiries') {
            return ! $this->hasStuckEnquiries();
        }

        $factorKey = (string) ($meta['factor'] ?? '');
        $factor = $factors->get($factorKey);
        if (! is_array($factor)) {
            return false;
        }

        if (! ($factor['available'] ?? false) || $factor['score'] === null) {
            // Missing finance/ops data → tip stays open so they start logging.
            return false;
        }

        $min = (float) ($meta['min_score'] ?? 80);

        return (float) $factor['score'] >= $min;
    }

    private function hasStuckEnquiries(): bool
    {
        if (! Schema::hasTable('enquiries') || ! config('libcontrol.modules.enquiries')) {
            return false;
        }

        return Enquiry::query()
            ->where('status', 'new')
            ->where('created_at', '<', now()->subDays(14))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{label: string, url: string}
     */
    private function resolveCta(array $meta): array
    {
        $label = (string) ($meta['cta_label'] ?? 'View Details');
        $params = is_array($meta['cta_route_params'] ?? null) ? $meta['cta_route_params'] : [];
        $fragment = trim((string) ($meta['cta_fragment'] ?? ''), '#');
        $routes = array_values(array_filter([
            $meta['cta_route'] ?? null,
            $meta['cta_route_fallback'] ?? null,
        ]));

        foreach ($routes as $name) {
            if (! is_string($name) || ! Route::has($name)) {
                continue;
            }

            $url = route($name, $params);
            if ($fragment !== '') {
                $url .= '#'.$fragment;
            }

            return ['label' => $label, 'url' => $url];
        }

        if (Route::has('branch.index')) {
            return ['label' => $label !== 'View Details' ? $label : 'Open Branch', 'url' => route('branch.index')];
        }

        if (Route::has('dashboard')) {
            return ['label' => $label !== 'View Details' ? $label : 'Open Dashboard', 'url' => route('dashboard')];
        }

        return ['label' => $label, 'url' => '#'];
    }
}
