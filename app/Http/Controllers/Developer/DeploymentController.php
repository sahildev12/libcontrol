<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\RemoteManagePlanRequest;
use App\Http\Requests\StoreLicensedDeploymentRequest;
use App\Http\Requests\UpdateLicensedDeploymentRequest;
use App\Models\LicensedDeployment;
use App\Services\Developer\DeploymentIndexService;
use App\Services\Developer\DeploymentRemoteManageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeploymentController extends Controller
{
    public function __construct(
        private DeploymentRemoteManageService $remoteManage,
        private DeploymentIndexService $deploymentIndex,
    ) {}

    public function index(Request $request): View
    {
        return view('developer.deployments.index', [
            'stats' => $this->deploymentIndex->stats(),
            'unauthorizedRows' => $this->deploymentIndex->unauthorizedDomainRows(),
            'licenseRows' => $this->deploymentIndex->authorizedLicenseRows(),
            'activeTab' => in_array($request->string('tab')->toString(), ['unauthorized', 'authorized'], true)
                ? $request->string('tab')->toString()
                : 'unauthorized',
        ]);
    }

    public function create(Request $request): View
    {
        $domain = LicensedDeployment::normalizeDomain($request->string('domain')->toString());

        return view('developer.deployments.create', [
            'prefillClientName' => $request->string('client_name')->toString() ?: $this->deploymentIndex->suggestedClientNameFromDomain($domain),
            'prefillDomains' => $domain,
        ]);
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

        return redirect()
            ->route('developer.deployments.edit', $deployment)
            ->with('issued_license_key', $licenseKey)
            ->with('status', 'Deployment created. Copy the license key now — it will not be shown again.');
    }

    public function edit(LicensedDeployment $deployment): View
    {
        return view('developer.deployments.edit', [
            'deployment' => $deployment,
            'domainsText' => implode("\n", $deployment->allowed_domains ?? []),
        ]);
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
            ->route('developer.deployments.edit', $deployment)
            ->with('status', 'Deployment updated.');
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

        return redirect()
            ->route('developer.deployments.edit', $deployment)
            ->with('issued_license_key', $licenseKey)
            ->with('status', 'New license key issued. Update the client .env file.');
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
