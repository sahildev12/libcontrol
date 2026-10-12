<?php

use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\PhonePeWebhookController;
use App\Http\Controllers\Api\RuntimeSyncController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\PaymentReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['license_server', 'throttle:60,1'])
    ->group(function () {
        Route::post('/api/runtime/sync', RuntimeSyncController::class)
            ->name('runtime.sync');
        Route::post('/api/support/tickets', [SupportTicketController::class, 'store'])
            ->name('support.tickets.store');
        Route::post('/api/support/tickets/pull', [SupportTicketController::class, 'pull'])
            ->name('support.tickets.pull');
        Route::post('/api/payments/checkout', [PaymentApiController::class, 'checkout'])
            ->name('payments.api.checkout');
        Route::post('/api/payments/summary', [PaymentApiController::class, 'summary'])
            ->name('payments.api.summary');
        Route::get('/payments/{uuid}/return', PaymentReturnController::class)
            ->whereUuid('uuid')
            ->name('payments.return');
    });

Route::post('/api/webhooks/phonepe', PhonePeWebhookController::class)
    ->middleware(['license_server', 'throttle:240,1'])
    ->name('payments.webhooks.phonepe');
