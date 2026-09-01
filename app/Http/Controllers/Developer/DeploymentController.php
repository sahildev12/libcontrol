<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicensedDeploymentRequest;
use App\Http\Requests\UpdateLicensedDeploymentRequest;
use App\Models\InstallationEvent;
use App\Models\LicensedDeployment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeploymentController extends Controller
{
    public function index(): View
    {
        $deployments = LicensedDeployment::query()
            ->orderByDesc('updated_at')
            ->get();

        $recentInstallations = InstallationEvent::query()
            ->orderByDesc('last_seen_at')
            ->limit(10)
            ->get();

        return view('developer.deployments.index', compact('deployments', 'recentInstallations'));
    }

    public function create(): View
    {
        return view('developer.deployments.create');
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

    public function installations(Request $request): View
    {
        $query = InstallationEvent::query()->orderByDesc('last_seen_at');

        if ($request->string('filter')->toString() === 'unauthorized') {
            $query->where('is_authorized', false);
        }

        $events = $query->paginate(25)->withQueryString();

        return view('developer.deployments.installations', compact('events'));
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
