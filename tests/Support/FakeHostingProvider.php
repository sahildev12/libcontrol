<?php

namespace Tests\Support;

use App\Services\Hosting\HostingProvider;
use App\Services\Hosting\LocalHostingProvider;

class FakeHostingProvider implements HostingProvider
{
    /** @var list<string> */
    public array $subdomains = [];

    /** @var list<string> */
    public array $deletedSubdomains = [];

    /** @var list<string> */
    public array $deletedDatabases = [];

    public int $existingDatabases = 0;

    public ?\Throwable $failCreateDatabase = null;

    public bool $sslActive = true;

    private LocalHostingProvider $local;

    public function __construct()
    {
        $this->local = new LocalHostingProvider;
    }

    public function subdomainExists(string $subdomain): bool
    {
        return in_array($subdomain, $this->subdomains, true);
    }

    public function createSubdomain(string $subdomain): void
    {
        $this->subdomains[] = $subdomain;
    }

    public function deleteSubdomain(string $subdomain): void
    {
        $this->deletedSubdomains[] = $subdomain;
        $this->subdomains = array_values(array_diff($this->subdomains, [$subdomain]));
    }

    public function sslActive(string $subdomain): bool
    {
        return $this->sslActive;
    }

    public function createDatabase(string $name, string $password): array
    {
        if ($this->failCreateDatabase) {
            throw $this->failCreateDatabase;
        }

        return $this->local->createDatabase($name, $password);
    }

    public function deleteDatabase(string $database): void
    {
        $this->deletedDatabases[] = $database;
        $this->local->deleteDatabase($database);
    }

    public function databaseCount(): int
    {
        return $this->existingDatabases;
    }
}
