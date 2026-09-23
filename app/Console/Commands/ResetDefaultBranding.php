<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\PlatformSetting;
use Illuminate\Console\Command;

class ResetDefaultBranding extends Command
{
    protected $signature = 'libcontrol:reset-branding {--branches : Also clear per-branch logo overrides}';

    protected $description = 'Use bundled yellow-lc-logo and lc-logo-landscape (clears uploaded logo paths in settings)';

    public function handle(): int
    {
        $settings = PlatformSetting::current();
        $settings->update([
            'logo_path' => null,
            'simple_logo_path' => null,
            'logo_with_text_path' => null,
            'favicon_path' => null,
            'id_card_logo_path' => null,
            'website_logo_path' => null,
        ]);

        $this->info('Platform branding paths cleared — defaults from public/logo/png-background/ will be used.');

        if ($this->option('branches')) {
            $columns = array_values(array_filter(
                ['logo_path', 'logo_with_text_path', 'simple_logo_path', 'favicon_path'],
                fn (string $column) => \Illuminate\Support\Facades\Schema::hasColumn('branches', $column),
            ));

            if ($columns !== []) {
                $payload = array_fill_keys($columns, null);
                Branch::query()->update($payload);
                $this->info('Branch logo overrides cleared.');
            }
        }

        return self::SUCCESS;
    }
}
