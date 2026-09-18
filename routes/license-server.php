<?php

use App\Http\Controllers\Api\RuntimeSyncController;
use App\Http\Controllers\Api\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['license_server', 'throttle:60,1'])
    ->group(function () {
        Route::post('/api/runtime/sync', RuntimeSyncController::class)
            ->name('runtime.sync');
        Route::post('/api/support/tickets', [SupportTicketController::class, 'store'])
            ->name('support.tickets.store');
    });
