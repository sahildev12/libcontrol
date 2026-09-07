<?php

namespace App\Providers;

use App\Services\Addons\AddonRegistry;
use Illuminate\Support\ServiceProvider;

class AddonBootstrapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AddonRegistry::class);
    }

    public function boot(): void
    {
        $this->app->make(AddonRegistry::class)->bootEnabledAddons();
    }
}
