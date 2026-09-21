<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/runtime/sync',
            'setup/install',
            'setup/test-database',
        ]);

        $middleware->alias([
            'branch' => \App\Http\Middleware\EnsureUserHasBranch::class,
            'platform_admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'client_admin' => \App\Http\Middleware\EnsureClientAdmin::class,
            'page.activity' => \App\Http\Middleware\RecordPageActivity::class,
            'developer_admin' => \App\Http\Middleware\EnsureDeveloperAdmin::class,
            'license_server' => \App\Http\Middleware\EnsureLicenseServer::class,
            'landlord_host' => \App\Http\Middleware\EnsureLandlordHost::class,
            'addon' => \App\Http\Middleware\EnsureAddonEnabled::class,
            'student.api' => \App\Http\Middleware\EnsureStudentApiUser::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\RedirectToSetup::class,
            \App\Http\Middleware\IdentifyTenant::class,
            \App\Http\Middleware\EnsureDeploymentLicensed::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureLibraryOperationsAccess::class,
            \App\Http\Middleware\RuntimeProbe::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (app()->runningUnitTests()) {
                return null;
            }

            if (! \App\Support\InstallState::needsInstallation()) {
                return null;
            }

            if (\App\Support\InstallState::allowsInstallerRequest($request->path())) {
                return null;
            }

            return redirect()->route('setup.show');
        });
    })->create();
