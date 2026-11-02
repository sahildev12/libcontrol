<?php

namespace App\Addons\ActivityLog;

use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ActivityLogAddonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Route::has('activity-logs.index')) {
            return;
        }

        Route::middleware(['web', 'auth', 'branch', 'page.activity', 'addon:activity-log'])->group(function (): void {
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::post('/activity-logs/bulk-delete', [ActivityLogController::class, 'bulkDestroy'])->name('activity-logs.bulk-destroy');
            Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
        });

        Route::getRoutes()->refreshNameLookups();
    }
}
