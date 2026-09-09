<?php

use App\Http\Controllers\Api\RuntimeSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['license_server', 'throttle:60,1'])
    ->group(function () {
        Route::post('/api/runtime/sync', RuntimeSyncController::class)
            ->name('runtime.sync');
    });
