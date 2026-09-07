<?php

namespace App\Addons\Attendance;

use App\Addons\Attendance\Http\Controllers\Api\AttendanceApiController;
use App\Addons\Attendance\Http\Controllers\Api\AuthApiController;
use App\Addons\Attendance\Http\Controllers\AttendanceController;
use App\Addons\Attendance\Http\Controllers\PublicCheckInController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AttendanceAddonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Route::has('attendance.index')) {
            return;
        }

        Route::middleware(['web', 'addon:attendance'])->group(function (): void {
            Route::get('/attendance/check-in/{token}', [PublicCheckInController::class, 'show'])
                ->name('attendance.public.check-in');
            Route::post('/attendance/check-in/{token}', [PublicCheckInController::class, 'store'])
                ->middleware('throttle:12,1')
                ->name('attendance.public.check-in.store');
        });

        Route::middleware(['web', 'auth', 'branch', 'page.activity', 'addon:attendance'])->group(function (): void {
            Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::post('/attendance/mark-present', [AttendanceController::class, 'markPresent'])->name('attendance.mark-present');
            Route::get('/attendance/students/{student}/profile', [AttendanceController::class, 'studentProfile'])->name('attendance.student-profile');
            Route::get('/attendance/settings', [AttendanceController::class, 'settings'])->name('attendance.settings');
            Route::patch('/attendance/settings', [AttendanceController::class, 'updateSettings'])->name('attendance.settings.update');
            Route::post('/attendance/settings/rotate-qr', [AttendanceController::class, 'rotateQr'])->name('attendance.settings.rotate-qr');
            Route::get('/attendance/reports', [AttendanceController::class, 'reports'])->name('attendance.reports');
        });

        Route::middleware(['api', 'addon:attendance'])->prefix('api/v1')->group(function (): void {
            Route::post('/auth/login', [AuthApiController::class, 'login']);

            Route::middleware('auth:sanctum')->group(function (): void {
                Route::post('/auth/logout', [AuthApiController::class, 'logout']);
                Route::get('/auth/me', [AuthApiController::class, 'me']);
                Route::get('/attendance/context', [AttendanceApiController::class, 'context']);
                Route::post('/attendance/check-in', [AttendanceApiController::class, 'checkIn']);
                Route::post('/attendance/check-in/bulk', [AttendanceApiController::class, 'bulkCheckIn']);
                Route::get('/attendance/history', [AttendanceApiController::class, 'history']);
            });
        });

        Route::getRoutes()->refreshNameLookups();
    }
}
