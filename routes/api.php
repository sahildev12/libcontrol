<?php

use App\Http\Controllers\Api\StudentAuthApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/student')->group(function (): void {
    Route::post('/auth/check-code', [StudentAuthApiController::class, 'checkCode'])
        ->middleware('throttle:12,1');
    Route::post('/auth/setup-pin', [StudentAuthApiController::class, 'setupPin'])
        ->middleware('throttle:12,1');
    Route::post('/auth/login', [StudentAuthApiController::class, 'login'])
        ->middleware('throttle:12,1');

    Route::middleware(['auth:sanctum', 'student.api'])->group(function (): void {
        Route::post('/auth/logout', [StudentAuthApiController::class, 'logout']);
        Route::get('/auth/me', [StudentAuthApiController::class, 'me']);
    });
});
