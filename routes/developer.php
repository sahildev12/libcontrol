<?php

use App\Http\Controllers\Developer\DeploymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'platform_admin', 'developer_admin', 'license_server', 'landlord_host'])
    ->prefix('developer/deployments')
    ->name('developer.deployments.')
    ->group(function () {
        Route::get('/', [DeploymentController::class, 'index'])->name('index');
        Route::get('/installations', [DeploymentController::class, 'installations'])->name('installations');
        Route::get('/create', [DeploymentController::class, 'create'])->name('create');
        Route::post('/', [DeploymentController::class, 'store'])->name('store');
        Route::get('/{deployment}/edit', [DeploymentController::class, 'edit'])->name('edit');
        Route::patch('/{deployment}', [DeploymentController::class, 'update'])->name('update');
        Route::delete('/{deployment}', [DeploymentController::class, 'destroy'])->name('destroy');
        Route::post('/{deployment}/regenerate-key', [DeploymentController::class, 'regenerateKey'])->name('regenerate-key');
    });
