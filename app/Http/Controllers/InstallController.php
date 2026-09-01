<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends Controller
{
    public function show(Request $request): View|Response
    {
        if ($this->isInstalled()) {
            return response('LibSpace is already installed. Delete storage/app/install.lock only if you need to reinstall.', 403);
        }

        if (! $this->tokenIsValid($request)) {
            abort(403, 'Invalid install token. Check LIBSPACE_SETUP_TOKEN in your .env file.');
        }

        return view('install.index', [
            'appUrl' => config('app.url'),
            'dbDatabase' => config('database.connections.mysql.database'),
        ]);
    }

    public function run(Request $request): Response
    {
        if ($this->isInstalled()) {
            return response()->json(['message' => 'Already installed.'], 403);
        }

        if (! $this->tokenIsValid($request)) {
            return response()->json(['message' => 'Invalid install token.'], 403);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => 'ClientInstallSeeder', '--force' => true]);

            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            File::ensureDirectoryExists(storage_path('app'));
            File::put(storage_path('app/install.lock'), now()->toIso8601String());

            return response()->json([
                'message' => 'Installation complete. You can now log in.',
                'login_url' => route('login'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Installation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    private function isInstalled(): bool
    {
        return File::exists(storage_path('app/install.lock'));
    }

    private function tokenIsValid(Request $request): bool
    {
        $expected = (string) config('libspace.install.token', '');

        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, (string) $request->query('token', ''));
    }
}
