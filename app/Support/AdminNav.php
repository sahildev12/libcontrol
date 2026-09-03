<?php

namespace App\Support;

class AdminNav
{
    public static function isActive(?string $currentRoute, array $item): bool
    {
        if ($currentRoute === null || empty($item['route'])) {
            return false;
        }

        if ($currentRoute === $item['route']) {
            return true;
        }

        if (isset($item['active_prefix'])) {
            $prefix = (string) $item['active_prefix'];

            return $prefix !== '' && str_starts_with($currentRoute, $prefix);
        }

        $route = (string) $item['route'];
        $prefix = str_ends_with($route, '.index')
            ? substr($route, 0, -strlen('index'))
            : $route.'.';

        return $prefix !== '' && str_starts_with($currentRoute, $prefix);
    }
}
