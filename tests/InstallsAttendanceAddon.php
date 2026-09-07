<?php

namespace Tests;

use App\Services\Addons\AddonRegistry;

trait InstallsAttendanceAddon
{
    protected function installAttendanceAddon(): void
    {
        $registry = app(AddonRegistry::class);

        if (! $registry->isInstalled('attendance')) {
            $registry->install('attendance');
        } elseif (! $registry->isEnabled('attendance')) {
            $registry->enable('attendance');
        }
    }
}
