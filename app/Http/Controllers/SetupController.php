<?php

namespace App\Http\Controllers;

use App\Services\Setup\SetupInstaller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SetupController extends Controller
{
    public function __construct(
        private SetupInstaller $installer,
    ) {}

    public function show(): View|Response
    {
        if ($this->installer->isInstalled()) {
            return redirect()->route('login');
        }

        return view('setup.index', [
            'requirements' => $this->installer->requirements(),
            'detectedUrl' => $this->detectedUrl(),
            'defaultBaseDomain' => $this->defaultBaseDomain(),
        ]);
    }

    public function testDatabase(Request $request): JsonResponse
    {
        if ($this->installer->isInstalled()) {
            return response()->json(['message' => 'Already installed.'], 403);
        }

        $validated = $request->validate([
            'db_host' => ['required', 'string', 'max:120'],
            'db_port' => ['required', 'string', 'max:10'],
            'db_database' => ['required', 'string', 'max:120'],
            'db_username' => ['required', 'string', 'max:120'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->installer->testAndMigrateDatabase($validated);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Database setup failed: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Database connected and migrated successfully.',
            'migrated' => true,
        ]);
    }

    public function install(Request $request): JsonResponse
    {
        if ($this->installer->isInstalled()) {
            return response()->json(['message' => 'Already installed.'], 403);
        }

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
            'timezone' => ['required', 'string', 'max:64'],
            'db_host' => ['required', 'string', 'max:120'],
            'db_port' => ['required', 'string', 'max:10'],
            'db_database' => ['required', 'string', 'max:120'],
            'db_username' => ['required', 'string', 'max:120'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'tenancy_enabled' => ['sometimes', 'boolean'],
            'tenant_base_domain' => ['nullable', 'string', 'max:120'],
            'tenant_landlord_hosts' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        try {
            $this->installer->install($validated);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Setup failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'LibSpace is ready.',
            'login_url' => url('/admin/login'),
        ]);
    }

    private function detectedUrl(): string
    {
        if (app()->runningInConsole()) {
            return 'http://localhost';
        }

        return request()->getSchemeAndHttpHost();
    }

    private function defaultBaseDomain(): string
    {
        $host = parse_url($this->detectedUrl(), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return 'phenomit.com';
        }

        $parts = explode('.', $host);

        if (count($parts) >= 2) {
            return implode('.', array_slice($parts, -2));
        }

        return $host;
    }
}
