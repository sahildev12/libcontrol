<?php

namespace Tests;

use App\Services\Addons\AddonRegistry;

trait InstallsActivityLogAddon
{
    protected function installActivityLogAddon(): void
    {
        $registry = app(AddonRegistry::class);

        if (! $registry->isInstalled('activity-log')) {
            $registry->install('activity-log');
        } elseif (! $registry->isEnabled('activity-log')) {
            $registry->enable('activity-log');
        }
    }
}
