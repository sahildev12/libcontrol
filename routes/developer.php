<?php

use App\Http\Controllers\Developer\DeploymentController;
use App\Http\Controllers\Developer\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'developer_admin', 'license_server', 'landlord_host'])
    ->prefix('developer/support-tickets')
    ->name('developer.support-tickets.')
    ->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/{supportTicket}', [SupportTicketController::class, 'show'])->name('show');
        Route::patch('/{supportTicket}', [SupportTicketController::class, 'update'])->name('update');
    });

Route::middleware(['auth', 'developer_admin', 'license_server', 'landlord_host'])
    ->prefix('developer/deployments')
    ->name('developer.deployments.')
    ->group(function () {
        Route::get('/', [DeploymentController::class, 'index'])->name('index');
        Route::get('/installations', [DeploymentController::class, 'installations'])->name('installations');
        Route::get('/create', [DeploymentController::class, 'create'])->name('create');
        Route::post('/', [DeploymentController::class, 'store'])->name('store');
        Route::get('/{deployment}/manage', [DeploymentController::class, 'manage'])->name('manage');
        Route::post('/{deployment}/manage/plan', [DeploymentController::class, 'updatePlan'])->name('manage.plan');
        Route::post('/{deployment}/manage/command', [DeploymentController::class, 'queueCommand'])->name('manage.command');
        Route::get('/{deployment}/edit', [DeploymentController::class, 'edit'])->name('edit');
        Route::patch('/{deployment}', [DeploymentController::class, 'update'])->name('update');
        Route::delete('/{deployment}', [DeploymentController::class, 'destroy'])->name('destroy');
        Route::post('/{deployment}/regenerate-key', [DeploymentController::class, 'regenerateKey'])->name('regenerate-key');
    });
