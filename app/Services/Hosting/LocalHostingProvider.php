<?php

namespace App\Services\Hosting;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Development stand-in: databases are created with the landlord credentials and subdomains are not real.
 */
class LocalHostingProvider implements HostingProvider
{
    private const PREFIX = 'lc_trial_';

    public function subdomainExists(string $subdomain): bool
    {
        return false;
    }

    public function createSubdomain(string $subdomain): void {}

    public function deleteSubdomain(string $subdomain): void {}

    public function sslActive(string $subdomain): bool
    {
        return true;
    }

    public function createDatabase(string $name, string $password): array
    {
        $database = self::PREFIX.$name;

        if ($this->driver() === 'sqlite') {
            $path = database_path($database.'.sqlite');
            File::put($path, '');

            return ['database' => $path, 'username' => null];
        }

        $this->landlord()->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        return ['database' => $database, 'username' => null];
    }

    public function deleteDatabase(string $database): void
    {
        if ($this->driver() === 'sqlite') {
            if (str_starts_with(basename($database), self::PREFIX)) {
                File::delete($database);
            }

            return;
        }

        if (! str_starts_with($database, self::PREFIX)) {
            throw new \RuntimeException("Refusing to drop non-trial database {$database}.");
        }

        $this->landlord()->statement("DROP DATABASE IF EXISTS `{$database}`");
    }

    public function databaseCount(): int
    {
        if ($this->driver() === 'sqlite') {
            return count(File::glob(database_path(self::PREFIX.'*.sqlite')));
        }

        return count($this->landlord()->select(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME LIKE ?',
            [self::PREFIX.'%'],
        ));
    }

    private function landlord(): Connection
    {
        return DB::connection((string) config('libcontrol.tenancy.landlord_connection', 'mysql'));
    }

    private function driver(): string
    {
        return (string) $this->landlord()->getDriverName();
    }
}
