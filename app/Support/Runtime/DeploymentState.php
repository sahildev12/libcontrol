<?php

namespace App\Support\Runtime;

use Illuminate\Support\Facades\Cache;

class DeploymentState
{
    public const CACHE_KEY = 'runtime_sync_state';

    /**
     * @return array{status: string, authorized: bool, grace_until: ?string, checked_at: ?string}
     */
    public function current(): array
    {
        return Cache::get(self::CACHE_KEY, $this->defaultState());
    }

    /**
     * @param  array{status?: string, authorized?: bool, grace_until?: ?string}  $state
     */
    public function store(array $state): void
    {
        Cache::put(self::CACHE_KEY, [
            'status' => $state['status'] ?? 'pending',
            'authorized' => (bool) ($state['authorized'] ?? false),
            'grace_until' => $state['grace_until'] ?? null,
            'checked_at' => now()->toIso8601String(),
        ], now()->addDays(30));
    }

    public function isBlocked(): bool
    {
        $state = $this->current();

        if ($state['authorized']) {
            return false;
        }

        if (empty($state['grace_until'])) {
            return false;
        }

        return now()->isAfter(\Illuminate\Support\Carbon::parse($state['grace_until']));
    }

    public function shouldSync(): bool
    {
        $state = $this->current();
        $checkedAt = $state['checked_at'] ?? null;

        if (! $checkedAt) {
            return true;
        }

        $interval = (int) config('libspace.deployment.sync_interval', 3600);

        return now()->diffInSeconds(\Illuminate\Support\Carbon::parse($checkedAt)) >= $interval;
    }

    /**
     * @return array{status: string, authorized: bool, grace_until: ?string, checked_at: ?string}
     */
    private function defaultState(): array
    {
        return [
            'status' => 'pending',
            'authorized' => false,
            'grace_until' => null,
            'checked_at' => null,
        ];
    }
}
