<?php

use App\Http\Controllers\Developer\TenantController;
use App\Http\Controllers\Developer\TenantPortalSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'developer_admin', 'landlord_host'])
    ->prefix('developer/portals')
    ->name('developer.portals.')
    ->group(function () {
        Route::get('/', [TenantPortalSettingsController::class, 'index'])->name('index');
        Route::get('/{tenant}/settings', [TenantPortalSettingsController::class, 'show'])->name('settings');
        Route::post('/{tenant}/switch-branch', [TenantPortalSettingsController::class, 'switchBranch'])->name('switch-branch');
        Route::patch('/{tenant}/settings/branch', [TenantPortalSettingsController::class, 'updateBranch'])->name('settings.branch.update');
        Route::patch('/{tenant}/settings/platform', [TenantPortalSettingsController::class, 'updatePlatform'])->name('settings.platform.update');
        Route::post('/{tenant}/settings/website', [TenantPortalSettingsController::class, 'updateWebsite'])->name('settings.website.update');
        Route::patch('/{tenant}/settings/email-notifications', [TenantPortalSettingsController::class, 'updateEmailNotifications'])->name('settings.email-notifications.update');
        Route::patch('/{tenant}/settings/global', [TenantPortalSettingsController::class, 'updateGlobal'])->name('settings.global.update');
    });

Route::middleware(['auth', 'developer_admin', 'landlord_host'])
    ->prefix('developer/tenants')
    ->name('developer.tenants.')
    ->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('index');
        Route::get('/create', [TenantController::class, 'create'])->name('create');
        Route::post('/prepare-database', [TenantController::class, 'prepareDatabase'])->name('prepare-database');
        Route::post('/', [TenantController::class, 'store'])->name('store');
        Route::get('/{tenant}/manage', [TenantController::class, 'manage'])->name('manage');
        Route::post('/{tenant}/manage/plan', [TenantController::class, 'updateRemotePlan'])->name('manage.plan');
        Route::post('/{tenant}/manage/action', [TenantController::class, 'runAction'])->name('manage.action');
        Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->name('edit');
        Route::patch('/{tenant}', [TenantController::class, 'update'])->name('update');
    });
