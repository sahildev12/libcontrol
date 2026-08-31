<?php

use App\Console\Commands\SendPlanExpiryReminders;
use App\Console\Commands\SyncRuntimeMetrics;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendPlanExpiryReminders::class)
    ->dailyAt('09:00')
    ->timezone(config('libspace.timezone', 'Asia/Kolkata'))
    ->description('Send plan expiry reminder emails to students');

Schedule::command(SyncRuntimeMetrics::class)
    ->dailyAt('03:15')
    ->timezone(config('libspace.timezone', 'Asia/Kolkata'))
    ->description('Synchronize runtime metrics');
