<?php

use App\Console\Commands\ReconcilePayments;
use App\Console\Commands\SendFeeAlerts;
use App\Console\Commands\SendGrowthCampaignEmails;
use App\Console\Commands\SendMarketingCampaignEmails;
use App\Console\Commands\SendPlanExpiryReminders;
use App\Console\Commands\SendSeatAlerts;
use App\Console\Commands\SendStudentBirthdayEmails;
use App\Console\Commands\SendStudentRecoveryEmails;
use App\Console\Commands\SendSubscriptionTrialEmails;
use App\Console\Commands\SyncRuntimeMetrics;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendPlanExpiryReminders::class)
    ->dailyAt('09:00')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send plan expiry reminder emails to students');

Schedule::command(SendFeeAlerts::class)
    ->dailyAt('09:15')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send fee due and overdue reminder emails');

Schedule::command(SendSeatAlerts::class)
    ->dailyAt('09:30')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send seat expiry sequence and owner occupancy digests');

Schedule::command(SendSubscriptionTrialEmails::class)
    ->dailyAt('10:00')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send LibControl subscription trial conversion emails');

Schedule::command(SendGrowthCampaignEmails::class)
    ->weeklyOn(2, '11:00')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send Grow Your Library lifecycle emails to owners');

Schedule::command(SendMarketingCampaignEmails::class)
    ->weeklyOn(4, '11:30')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send opted-in marketing / product update emails');

Schedule::command(SyncRuntimeMetrics::class)
    ->dailyAt('03:15')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Synchronize runtime metrics');

Schedule::command(SendStudentBirthdayEmails::class)
    ->dailyAt('08:30')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send birthday emails to students');

Schedule::command(ReconcilePayments::class)
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->description('Confirm pending PhonePe payments');

Schedule::command(SendStudentRecoveryEmails::class)
    ->weeklyOn(1, '10:00')
    ->timezone(config('libcontrol.timezone', 'Asia/Kolkata'))
    ->description('Send recovery emails to inactive students');
