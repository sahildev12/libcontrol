<?php

namespace App\Support;

class PlanLimitPresentation
{
    /**
     * @param  array{limits: array<string, mixed>, usage: array<string, int>, remaining: array<string, ?int>}  $snapshot
     * @return list<'branches'|'halls'|'seats'>
     */
    public static function cappedDimensions(array $snapshot): array
    {
        $capped = [];

        foreach (['branches', 'halls', 'seats'] as $dimension) {
            $max = $snapshot['limits']['max_'.$dimension] ?? null;

            if ($max === null) {
                continue;
            }

            if (($snapshot['remaining'][$dimension] ?? 1) === 0) {
                $capped[] = $dimension;
            }
        }

        return $capped;
    }

    /**
     * @param  list<'branches'|'halls'|'seats'>  $capped
     */
    public static function summaryLine(array $snapshot, array $capped): string
    {
        if ($capped === []) {
            return '';
        }

        $limits = $snapshot['limits'];
        $usage = $snapshot['usage'];
        $parts = [];

        foreach ($capped as $dimension) {
            $label = match ($dimension) {
                'branches' => 'branches',
                'halls' => 'halls',
                'seats' => 'seats',
                default => $dimension,
            };
            $max = $limits['max_'.$dimension];
            $parts[] = "{$label} {$usage[$dimension]}/{$max}";
        }

        return implode(' · ', $parts);
    }

    public static function libcontrolPricingUrl(): string
    {
        $companyUrl = rtrim((string) config('libcontrol.product.company_url', 'https://katsindia.com'), '/');

        if (str_contains($companyUrl, 'libcontrol.in')) {
            return $companyUrl.'/#pricing';
        }

        return 'https://libcontrol.in/#pricing';
    }
}
