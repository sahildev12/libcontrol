<?php

namespace App\Console\Commands;

use App\Services\Addons\AddonRegistry;
use Illuminate\Console\Command;

class InstallAddon extends Command
{
    protected $signature = 'addon:install {slug : Addon slug from config/addons.php}';

    protected $description = 'Install and enable a LibControl addon';

    public function handle(AddonRegistry $addons): int
    {
        $slug = (string) $this->argument('slug');

        if (! $addons->manifest($slug)) {
            $this->error("Unknown addon [{$slug}].");

            return self::FAILURE;
        }

        $addons->install($slug);
        $this->info("Addon [{$slug}] installed and enabled.");

        return self::SUCCESS;
    }
}
