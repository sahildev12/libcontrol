<?php

use App\Http\Controllers\Developer\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'platform_admin', 'developer_admin', 'landlord_host'])
    ->prefix('developer/tenants')
    ->name('developer.tenants.')
    ->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('index');
        Route::get('/create', [TenantController::class, 'create'])->name('create');
        Route::post('/prepare-database', [TenantController::class, 'prepareDatabase'])->name('prepare-database');
        Route::post('/', [TenantController::class, 'store'])->name('store');
        Route::get('/{tenant}/edit', [TenantController::class, 'edit'])->name('edit');
        Route::patch('/{tenant}', [TenantController::class, 'update'])->name('update');
    });
