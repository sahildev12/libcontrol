<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\RemoteManagePlanRequest;
use App\Http\Requests\StoreLicensedDeploymentRequest;
use App\Http\Requests\UpdateLicensedDeploymentRequest;
use App\Models\LicensedDeployment;
use App\Services\Developer\DeploymentCommandService;
use App\Services\Developer\DeploymentIndexService;
use App\Services\Developer\DeploymentRemoteManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeploymentController extends Controller
{
    public function __construct(
        private DeploymentRemoteManageService $remoteManage,
        private DeploymentIndexService $deploymentIndex,
        private DeploymentCommandService $deploymentCommands,
    ) {}

    public function index(Request $request): View
    {
        $activeTab = in_array($request->string('tab')->toString(), ['unauthorized', 'authorized'], true)
            ? $request->string('tab')->toString()
            : 'authorized';

        $prefillDomain = LicensedDeployment::normalizeDomain($request->string('domain')->toString());
        $prefillClientName = $request->string('client_name')->toString();

        if ($prefillClientName === '' && $prefillDomain !== '') {
            $prefillClientName = $this->deploymentIndex->suggestedClientNameFromDomain($prefillDomain);
        }

        return view('developer.deployments.index', [
            'stats' => $this->deploymentIndex->stats(),
            'unauthorizedRows' => $this->deploymentIndex->unauthorizedDomainRows(),
            'licenseRows' => $this->deploymentIndex->authorizedLicenseRows(),
            'clientOptions' => $this->deploymentIndex->clientOptions(),
            'activeTab' => $activeTab,
            'openClientId' => $request->integer('client') ?: null,
            'prefillDomain' => $prefillDomain,
            'prefillClientName' => $prefillClientName,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $query = array_filter([
            'tab' => 'authorized',
            'action' => 'create',
            'domain' => $request->string('domain')->toString(),
            'client_name' => $request->string('client_name')->toString(),
        ]);

        return redirect()->route('developer.deployments.index', $query);
    }

    public function store(StoreLicensedDeploymentRequest $request): RedirectResponse
    {
        $licenseKey = LicensedDeployment::generateKey();

        $deployment = LicensedDeployment::query()->create([
            'client_name' => $request->string('client_name')->toString(),
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'allowed_domains' => $this->parseDomains($request->string('allowed_domains')->toString()),
            'grace_days' => (int) $request->input('grace_days'),
            'active' => $request->boolean('active', true),
            'notes' => $request->input('notes'),
        ]);

        $this->deploymentCommands->queueLicenseKeyUpdate($deployment, $licenseKey);

        return redirect()
            ->route('developer.deployments.index', ['tab' => 'authorized', 'client' => $deployment->id])
            ->with('issued_license_key', $licenseKey)
            ->with('status', 'Client authorized. The license key will be pushed to the client .env on the next sync.');
    }

    public function edit(LicensedDeployment $deployment): RedirectResponse
    {
        return redirect()->route('developer.deployments.index', [
            'tab' => 'authorized',
            'client' => $deployment->id,
        ]);
    }

    public function authorizeDomain(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
            'deployment_id' => ['nullable', 'integer', 'exists:licensed_deployments,id'],
            'client_name' => ['required_without:deployment_id', 'string', 'max:120'],
        ]);

        $domain = LicensedDeployment::normalizeDomain($validated['domain']);

        if ($domain === '') {
            return back()->withErrors(['domain' => 'Enter a valid domain.']);
        }

        if (! empty($validated['deployment_id'])) {
            $deployment = LicensedDeployment::query()->findOrFail($validated['deployment_id']);
            $domains = $deployment->allowed_domains ?? [];

            if (! in_array($domain, $domains, true)) {
                $domains[] = $domain;
            }

            $deployment->update([
                'allowed_domains' => $domains,
                'active' => true,
            ]);

            return redirect()
                ->route('developer.deployments.index', ['tab' => 'authorized', 'client' => $deployment->id])
                ->with('status', "Domain {$domain} is now authorized for {$deployment->client_name}.");
        }

        $licenseKey = LicensedDeployment::generateKey();
        $deployment = LicensedDeployment::query()->create([
            'client_name' => $validated['client_name'],
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
            'allowed_domains' => [$domain],
            'grace_days' => 7,
            'active' => true,
        ]);

        $this->deploymentCommands->queueLicenseKeyUpdate($deployment, $licenseKey);

        return redirect()
            ->route('developer.deployments.index', ['tab' => 'authorized', 'client' => $deployment->id])
            ->with('issued_license_key', $licenseKey)
            ->with('status', "Domain {$domain} authorized. The license key will be pushed to the client .env on the next sync.");
    }

    public function update(UpdateLicensedDeploymentRequest $request, LicensedDeployment $deployment): RedirectResponse
    {
        $deployment->update([
            'client_name' => $request->string('client_name')->toString(),
            'allowed_domains' => $this->parseDomains($request->string('allowed_domains')->toString()),
            'grace_days' => (int) $request->input('grace_days'),
            'active' => $request->boolean('active', true),
            'notes' => $request->input('notes'),
        ]);

        return redirect()
            ->route('developer.deployments.index', ['tab' => 'authorized', 'client' => $deployment->id])
            ->with('status', 'Client updated.');
    }

    public function updateDomains(Request $request, LicensedDeployment $deployment): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:120'],
            'allowed_domains' => ['required', 'string', 'max:2000'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $domains = $this->parseDomains($validated['allowed_domains']);

        if ($domains === []) {
            return response()->json([
                'message' => 'Add at least one domain.',
            ], 422);
        }

        $deployment->update([
            'client_name' => $validated['client_name'],
            'allowed_domains' => $domains,
            'active' => $request->boolean('active', true),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Domains saved.',
            'deployment' => [
                'id' => $deployment->id,
                'client_name' => $deployment->client_name,
                'domains' => implode(', ', $domains),
                'domains_text' => implode("\n", $domains),
                'domains_list' => $domains,
                'active' => $deployment->active,
                'status_label' => $deployment->active ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function destroy(LicensedDeployment $deployment): RedirectResponse
    {
        $deployment->delete();

        return redirect()
            ->route('developer.deployments.index')
            ->with('status', 'Deployment removed.');
    }

    public function installations(Request $request): RedirectResponse
    {
        $tab = $request->string('filter')->toString() === 'unauthorized'
            ? 'unauthorized'
            : 'authorized';

        return redirect()->route('developer.deployments.index', ['tab' => $tab]);
    }

    public function manage(LicensedDeployment $deployment): View
    {
        return view('developer.deployments.manage', $this->remoteManage->managePayload($deployment));
    }

    public function updatePlan(RemoteManagePlanRequest $request, LicensedDeployment $deployment): RedirectResponse
    {
        $this->remoteManage->updatePlan($deployment, $request->validated(), $request->user(), $request);

        return redirect()
            ->route('developer.deployments.manage', $deployment)
            ->with('status', 'Plan update queued. The client will apply it on the next sync heartbeat.');
    }

    public function queueCommand(Request $request, LicensedDeployment $deployment): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in([
                'clear_cache',
                'force_sync',
                'addon_install',
                'addon_enable',
                'addon_disable',
                'database_backup',
                'database_migrate',
                'database_restore',
            ])],
            'slug' => ['nullable', 'string', 'max:120'],
            'filename' => ['nullable', 'string', 'max:255'],
            'confirmation' => ['nullable', 'string', 'max:20'],
        ]);

        $action = $validated['action'];
        $payload = [];

        if (in_array($action, ['addon_install', 'addon_enable', 'addon_disable'], true)) {
            $payload['slug'] = $validated['slug'] ?? '';
        }

        if ($action === 'database_restore') {
            $payload['filename'] = $validated['filename'] ?? '';
            $payload['confirmation'] = $validated['confirmation'] ?? '';
        }

        $this->remoteManage->queueAction($deployment, $action, $payload, $request->user(), $request);

        return redirect()
            ->route('developer.deployments.manage', $deployment)
            ->with('status', ucfirst(str_replace('_', ' ', $action)).' queued for the client.');
    }

    public function regenerateKey(LicensedDeployment $deployment): RedirectResponse
    {
        $licenseKey = LicensedDeployment::generateKey();

        $deployment->update([
            'license_key_hash' => LicensedDeployment::hashKey($licenseKey),
        ]);

        $this->deploymentCommands->queueLicenseKeyUpdate($deployment, $licenseKey);

        return redirect()
            ->route('developer.deployments.index', ['tab' => 'authorized', 'client' => $deployment->id])
            ->with('issued_license_key', $licenseKey)
            ->with('status', 'New license key issued. It will be pushed to the client .env on the next sync.');
    }

    /**
     * @return list<string>
     */
    private function parseDomains(string $raw): array
    {
        $parts = preg_split('/[\s,]+/', $raw) ?: [];

        return collect($parts)
            ->map(fn (string $domain) => LicensedDeployment::normalizeDomain($domain))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
