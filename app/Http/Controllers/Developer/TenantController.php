<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(
        private TenantProvisioner $provisioner,
    ) {}

    public function index(): View
    {
        $tenants = Tenant::query()->orderByDesc('created_at')->get();

        return view('developer.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('developer.tenants.create', [
            'planTiers' => array_keys(config('libcontrol.plans', [])),
            'baseDomain' => config('libcontrol.tenancy.base_domain'),
        ]);
    }

    public function prepareDatabase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'database_name' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

        $databaseName = $validated['database_name'];

        try {
            $this->provisioner->migrateDatabase($databaseName);

            return response()->json([
                'message' => 'Database is ready. All tables were created successfully.',
                'migrated' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        if (! $this->provisioner->databaseExists($request->string('database_name')->toString())) {
            return back()
                ->withInput()
                ->withErrors([
                    'database_name' => 'Create this database in your hosting panel first, then try again.',
                ]);
        }

        $tenant = Tenant::query()->create([
            'client_name' => $request->string('client_name')->toString(),
            'subdomain' => $request->string('subdomain')->toString(),
            'database_name' => $request->string('database_name')->toString(),
            'plan_tier' => $request->string('plan_tier')->toString(),
            'max_seats_override' => $request->input('max_seats_override'),
            'max_halls_override' => $request->input('max_halls_override'),
            'max_branches_override' => $request->input('max_branches_override'),
            'notes' => $request->input('notes'),
            'active' => true,
        ]);

        try {
            $this->provisioner->provision(
                $tenant,
                $request->string('admin_email')->toString(),
                $request->string('admin_password')->toString(),
                $request->string('admin_name')->toString() ?: 'Library Admin',
            );
        } catch (\Throwable $e) {
            $tenant->delete();

            return back()
                ->withInput()
                ->withErrors(['database_name' => 'Provisioning failed: '.$e->getMessage()]);
        }

        return redirect()
            ->route('developer.tenants.index')
            ->with('status', "Client library created at {$tenant->url()}");
    }

    public function edit(Tenant $tenant): View
    {
        return view('developer.tenants.edit', [
            'tenant' => $tenant,
            'planTiers' => array_keys(config('libcontrol.plans', [])),
            'baseDomain' => config('libcontrol.tenancy.base_domain'),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update([
            'client_name' => $request->string('client_name')->toString(),
            'plan_tier' => $request->string('plan_tier')->toString(),
            'max_seats_override' => $request->input('max_seats_override'),
            'max_halls_override' => $request->input('max_halls_override'),
            'max_branches_override' => $request->input('max_branches_override'),
            'active' => $request->boolean('active', true),
            'notes' => $request->input('notes'),
        ]);

        $this->provisioner->syncPlan($tenant);

        return redirect()
            ->route('developer.tenants.edit', $tenant)
            ->with('status', 'Client library updated.');
    }
}
