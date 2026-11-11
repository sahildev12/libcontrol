<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\TrialSignupController;
use App\Services\LoginBrandingService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('branch/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('branch/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    // /login always means branch login.
    Route::get('login', function () {
        return redirect('/branch/login');
    })->middleware('redirect_signed_in_library')->name('trials.login');

    Route::get('admin/login', function (Request $request, LoginBrandingService $branding) {
        // Hub: library-owner login (email / Google / Facebook). Tenant: admin portal login.
        if (config('libcontrol.tenancy.enabled') && ! TenantContext::isTenantRequest()) {
            abort_unless(config('libcontrol.trials.enabled'), 404);

            return app(TrialSignupController::class)->login();
        }

        return app(AuthenticatedSessionController::class)->create($request, $branding);
    })->middleware('redirect_signed_in_library')->name('admin.login');

    Route::post('admin/login', function (Request $request) {
        if (config('libcontrol.tenancy.enabled') && ! TenantContext::isTenantRequest()) {
            abort_unless(config('libcontrol.trials.enabled'), 404);

            return app(TrialSignupController::class)->loginStore($request);
        }

        return app()->call([app(AuthenticatedSessionController::class), 'store']);
    })->middleware('throttle:10,1')->name('admin.login.store');

    if (config('libcontrol.license_server.enabled')) {
        Route::get('developer/login', [AuthenticatedSessionController::class, 'create'])->name('developer.login');
        Route::post('developer/login', [AuthenticatedSessionController::class, 'store'])->name('developer.login.store');
    }

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::redirect('admin/forgot-password', '/forgot-password?from=admin')->name('admin.password.request');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

Route::get('admin', function (Request $request) {
    if ($request->user()) {
        return redirect()->intended(route('dashboard', absolute: false));
    }

    return redirect()->route('admin.login');
})->name('portal.admin');

Route::get('developer', function (Request $request) {
    if (! config('libcontrol.license_server.enabled')) {
        return $request->user()
            ? redirect()->route('dashboard')
            : redirect()->route('admin.login');
    }

    if ($request->user()?->isDeveloperAdmin()) {
        return redirect()->route('developer.deployments.index');
    }

    if ($request->user()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('developer.login');
})->name('portal.developer');
