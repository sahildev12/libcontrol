<?php

namespace App\Services\Developer;

use App\Models\InstallationEvent;
use App\Models\LicensedDeployment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DeploymentIndexService
{
    /**
     * @return array{unauthorized: int, licenses: int, online: int}
     */
    public function stats(): array
    {
        return [
            'unauthorized' => count($this->unauthorizedDomainRows()),
            'licenses' => LicensedDeployment::query()->count(),
            'online' => InstallationEvent::query()
                ->where('is_authorized', true)
                ->pluck('domain')
                ->unique()
                ->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function unauthorizedDomainRows(): array
    {
        return InstallationEvent::query()
            ->where('is_authorized', false)
            ->orderByDesc('last_seen_at')
            ->get()
            ->unique('domain')
            ->values()
            ->map(fn (InstallationEvent $event) => [
                'id' => $event->id,
                'domain' => $event->domain,
                'app_url' => $event->app_url ?: '—',
                'app_url_link' => $event->app_url,
                'hits' => $event->hit_count,
                'reason' => $this->unauthorizedReason($event),
                'last_seen' => $event->last_seen_at?->format('d M Y, h:i A') ?: '—',
                'suggested_client_name' => $this->suggestedClientName($event),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, client_name: string, domains: list<string>}>
     */
    public function clientOptions(): array
    {
        return LicensedDeployment::query()
            ->orderBy('client_name')
            ->get(['id', 'client_name', 'allowed_domains'])
            ->map(fn (LicensedDeployment $deployment) => [
                'id' => $deployment->id,
                'client_name' => $deployment->client_name,
                'domains' => $deployment->allowed_domains ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function authorizedLicenseRows(): array
    {
        $lastSeenByDomain = InstallationEvent::query()
            ->where('is_authorized', true)
            ->get(['domain', 'last_seen_at'])
            ->groupBy('domain')
            ->map(fn (Collection $events) => $events->max('last_seen_at'));

        return LicensedDeployment::query()
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (LicensedDeployment $deployment) use ($lastSeenByDomain) {
                $domains = $deployment->allowed_domains ?? [];
                $lastSeen = collect($domains)
                    ->map(fn (string $domain) => $lastSeenByDomain->get($domain))
                    ->filter()
                    ->max();

                return [
                    'id' => $deployment->id,
                    'client_name' => $deployment->client_name,
                    'domains' => $domains !== [] ? implode(', ', $domains) : '—',
                    'domains_text' => implode("\n", $domains),
                    'domains_list' => $domains,
                    'grace_days' => $deployment->grace_days,
                    'active' => $deployment->active,
                    'status_label' => $deployment->active ? 'Active' : 'Inactive',
                    'last_seen' => $lastSeen instanceof CarbonInterface
                        ? $lastSeen->format('d M Y, h:i A')
                        : 'Not seen yet',
                    'manage_url' => route('developer.deployments.manage', $deployment),
                    'update_url' => route('developer.deployments.update-domains', $deployment),
                    'regenerate_url' => route('developer.deployments.regenerate-key', $deployment),
                ];
            })
            ->values()
            ->all();
    }

    private function unauthorizedReason(InstallationEvent $event): string
    {
        if ($event->license_key_hash === LicensedDeployment::discoveryKeyHash()) {
            return 'Discovery ping (no license key configured)';
        }

        return 'Domain not whitelisted or license key rejected';
    }

    public function suggestedClientNameFromDomain(string $domain): string
    {
        if ($domain === '') {
            return 'New client';
        }

        $parts = explode('.', $domain);

        return ucfirst($parts[0] ?? $domain);
    }

    private function suggestedClientName(InstallationEvent $event): string
    {
        return $this->suggestedClientNameFromDomain($event->domain);
    }
}
