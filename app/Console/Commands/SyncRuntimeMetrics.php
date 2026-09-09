<?php

namespace App\Console\Commands;

use App\Support\Runtime\SyncCoordinator;
use Illuminate\Console\Command;

class SyncRuntimeMetrics extends Command
{
    protected $signature = 'app:sync-runtime-metrics';

    protected $description = 'Synchronize runtime metrics with the remote coordinator';

    public function handle(SyncCoordinator $coordinator): int
    {
        $coordinator->sync();

        $publicUrl = trim((string) config('libcontrol.deployment.public_url', ''));
        $appUrl = $publicUrl !== '' ? $publicUrl : rtrim((string) config('app.url'), '/');
        $code = \App\Models\PlatformSetting::current()->library_code;

        $this->info('Synced library to Phenomit.');
        $this->line("  Library code: {$code}");
        $this->line("  Student app URL: {$appUrl}");

        $host = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $this->warn('  Phones cannot reach localhost. Set LIBCONTROL_PUBLIC_URL=http://YOUR_PC_IP:8000 in .env and run this again.');
        }

        return self::SUCCESS;
    }
}
