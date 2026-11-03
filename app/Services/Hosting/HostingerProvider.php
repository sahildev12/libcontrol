<?php

namespace App\Services\Hosting;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HostingerProvider implements HostingProvider
{
    public function __construct(
        private string $token,
        private string $account,
        private string $website,
        private string $baseUrl = 'https://developers.hostinger.com',
    ) {
        if ($this->token === '') {
            throw new \RuntimeException('HOSTINGER_API_TOKEN is not configured.');
        }
    }

    public function subdomainExists(string $subdomain): bool
    {
        $existing = Cache::remember($this->subdomainsCacheKey(), 60, function () {
            $rows = $this->request()
                ->get($this->accountPath("websites/{$this->website}/subdomains"))
                ->throw()
                ->json();

            return collect(is_array($rows) ? $rows : [])
                ->map(fn ($row) => strtolower((string) ($row['subdomain'] ?? '')))
                ->filter()
                ->values()
                ->all();
        });

        return in_array(strtolower($subdomain), $existing, true);
    }

    public function createSubdomain(string $subdomain): void
    {
        Cache::forget($this->subdomainsCacheKey());

        $this->request()
            ->post($this->accountPath("websites/{$this->website}/subdomains"), [
                'subdomain' => $subdomain,
                'is_using_public_directory' => true,
            ])
            ->throw();
    }

    public function deleteSubdomain(string $subdomain): void
    {
        Cache::forget($this->subdomainsCacheKey());

        $response = $this->request()
            ->delete($this->accountPath("websites/{$this->website}/subdomains/{$subdomain}"));

        $this->throwUnlessMissing($response);
    }

    public function sslActive(string $subdomain): bool
    {
        $response = $this->request()
            ->get($this->accountPath("websites/{$subdomain}.{$this->website}/ssl/status"));

        return $response->successful() && $response->json('status') === 'active';
    }

    public function createDatabase(string $name, string $password): array
    {
        $this->request()
            ->post($this->accountPath('databases'), [
                'name' => $name,
                'user' => $name,
                'password' => $password,
                'website_domain' => $this->website,
            ])
            ->throw();

        $full = $this->account.'_'.$name;

        return ['database' => $full, 'username' => $full];
    }

    public function deleteDatabase(string $database): void
    {
        $response = $this->request()->delete($this->accountPath("databases/{$database}"));

        $this->throwUnlessMissing($response);
    }

    /**
     * Counts every database on the hosting account; the plan limit is account-wide.
     */
    public function databaseCount(): int
    {
        $json = $this->request()
            ->get($this->accountPath('databases'), ['page' => 1, 'per_page' => 1])
            ->throw()
            ->json();

        return (int) ($json['meta']['total'] ?? count($json['data'] ?? []));
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, 1000, throw: false);
    }

    private function subdomainsCacheKey(): string
    {
        return "hostinger:subdomains:{$this->account}:{$this->website}";
    }

    private function accountPath(string $path): string
    {
        return "/api/hosting/v1/accounts/{$this->account}/{$path}";
    }

    private function throwUnlessMissing(Response $response): void
    {
        if ($response->status() !== 404) {
            $response->throw();
        }
    }
}
