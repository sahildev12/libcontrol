<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'branch' => \App\Http\Middleware\EnsureUserHasBranch::class,
            'platform_admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'page.activity' => \App\Http\Middleware\RecordPageActivity::class,
            'developer_admin' => \App\Http\Middleware\EnsureDeveloperAdmin::class,
            'license_server' => \App\Http\Middleware\EnsureLicenseServer::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureDeploymentLicensed::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\RuntimeProbe::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
