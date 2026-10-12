<?php

use App\Http\Controllers\Api\MobileLibraryProfileController;
use App\Http\Controllers\Api\MobileLibraryResolverController;
use App\Http\Controllers\Api\StudentAuthApiController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/mobile/library/styles', [MobileLibraryProfileController::class, 'styles'])
    ->middleware('throttle:60,1');

Route::get('/v1/mobile/libraries/{code}', [MobileLibraryResolverController::class, 'show'])
    ->where('code', '[0-9\\-]+')
    ->middleware('throttle:60,1');

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
