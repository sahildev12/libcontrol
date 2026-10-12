<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Paid-until state of this installation's LibControl subscription.
 * Installs without a paid-until date (offline or legacy billing) are never locked.
 */
class Subscription
{
    public const ACTIVE = 'active';

    public const ENDING_SOON = 'ending_soon';

    public const GRACE = 'grace';

    public const LOCKED = 'locked';

    /**
     * @return array{state: string, paid_until: ?Carbon, locks_at: ?Carbon, days_left: ?int}
     */
    public static function status(): array
    {
        $paidUntil = self::paidUntil();

        if (! $paidUntil) {
            return ['state' => self::ACTIVE, 'paid_until' => null, 'locks_at' => null, 'days_left' => null];
        }

        $locksAt = $paidUntil->copy()->addDays(max(0, (int) config('libcontrol.subscription.grace_days', 3)));
        $now = now();

        $state = match (true) {
            $now->gte($locksAt) => self::LOCKED,
            $now->gte($paidUntil) => self::GRACE,
            $now->gte($paidUntil->copy()->subDays(max(0, (int) config('libcontrol.subscription.warn_days', 7)))) => self::ENDING_SOON,
            default => self::ACTIVE,
        };

        $daysUntil = $state === self::GRACE ? $locksAt : $paidUntil;

        return [
            'state' => $state,
            'paid_until' => $paidUntil,
            'locks_at' => $locksAt,
            'days_left' => max(0, (int) ceil($now->diffInHours($daysUntil, false) / 24)),
        ];
    }

    public static function isLocked(): bool
    {
        return self::status()['state'] === self::LOCKED;
    }

    public static function enforced(): bool
    {
        return ! config('libcontrol.license_server.enabled');
    }

    private static function paidUntil(): ?Carbon
    {
        if (! self::enforced()) {
            return null;
        }

        try {
            $value = PlatformSetting::current()->plan_paid_until;
        } catch (QueryException) {
            return null;
        }

        return $value ? Carbon::parse($value) : null;
    }
}
