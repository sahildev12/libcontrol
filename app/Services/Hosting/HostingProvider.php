<?php

namespace App\Services\Hosting;

interface HostingProvider
{
    public function subdomainExists(string $subdomain): bool;

    /**
     * Points {subdomain}.{base domain} at the main application folder.
     */
    public function createSubdomain(string $subdomain): void;

    public function deleteSubdomain(string $subdomain): void;

    public function sslActive(string $subdomain): bool;

    /**
     * @return array{database: string, username: ?string} A null username means "use the landlord credentials".
     */
    public function createDatabase(string $name, string $password): array;

    public function deleteDatabase(string $database): void;

    public function databaseCount(): int;
}
