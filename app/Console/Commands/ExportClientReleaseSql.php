<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class ExportClientReleaseSql extends Command
{
    protected $signature = 'libspace:export-client-sql
        {--output= : Output SQL file path}
        {--admin-email=admin@your-domain.com : Default admin email in the seed data}
        {--admin-password=ChangeMeAfterLogin123! : Default admin password in the seed data}
        {--admin-name=Library Admin : Default admin display name}';

    protected $description = 'Export a fresh client database schema and seed data to a single SQL file';

    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'mysql') {
            $this->error('This exporter requires a MySQL connection in your local .env file.');

            return self::FAILURE;
        }

        $output = $this->option('output') ?: database_path('libspace-client-install.sql');
        $originalDatabase = config('database.connections.mysql.database');
        $exportDatabase = 'libspace_client_export_'.now()->format('YmdHis');

        $host = (string) config('database.connections.mysql.host');
        $port = (string) config('database.connections.mysql.port', '3306');
        $username = (string) config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');

        $mysqldump = $this->findMysqlDump();
        if (! $mysqldump) {
            $this->error('mysqldump was not found. Set MYSQLDUMP_PATH in .env or install MySQL client tools.');

            return self::FAILURE;
        }

        try {
            $this->info("Creating temporary database [{$exportDatabase}]...");
            DB::statement("CREATE DATABASE `{$exportDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            config(['database.connections.mysql.database' => $exportDatabase]);
            DB::purge('mysql');
            DB::reconnect('mysql');

            config([
                'libspace.install.admin_email' => (string) $this->option('admin-email'),
                'libspace.install.admin_password' => (string) $this->option('admin-password'),
                'libspace.install.admin_name' => (string) $this->option('admin-name'),
            ]);

            $this->info('Running migrations...');
            Artisan::call('migrate', ['--force' => true], $this->output);

            $this->info('Seeding client install data...');
            Artisan::call('db:seed', ['--class' => 'ClientInstallSeeder', '--force' => true], $this->output);

            $this->info('Exporting SQL dump...');
            $arguments = [
                $mysqldump,
                '--host='.$host,
                '--port='.$port,
                '--user='.$username,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--no-tablespaces',
                '--default-character-set=utf8mb4',
                $exportDatabase,
            ];

            if ($password !== '') {
                $arguments[] = '--password='.$password;
            }

            $result = Process::timeout(300)->run($arguments);

            if (! $result->successful()) {
                $this->error(trim($result->errorOutput()) ?: 'mysqldump failed.');

                return self::FAILURE;
            }

            $header = implode("\n", [
                '-- LibSpace client install SQL',
                '-- Generated: '.now()->toIso8601String(),
                '-- Default admin login:',
                '--   Email: '.$this->option('admin-email'),
                '--   Password: '.$this->option('admin-password'),
                '-- Import this file in phpMyAdmin, then edit .env and upload the app files.',
                '',
            ]);

            file_put_contents($output, $header.$result->output());

            $this->info("SQL export written to {$output}");

            return self::SUCCESS;
        } finally {
            config(['database.connections.mysql.database' => $originalDatabase]);
            DB::purge('mysql');
            DB::reconnect('mysql');

            DB::statement("DROP DATABASE IF EXISTS `{$exportDatabase}`");
        }
    }

    private function findMysqlDump(): ?string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'mysqldump',
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysqldump') {
                $result = Process::run(['where', 'mysqldump']);
                if ($result->successful()) {
                    $path = trim(strtok($result->output(), PHP_EOL));
                    if ($path !== '') {
                        return $path;
                    }
                }

                continue;
            }

            if (is_string($candidate) && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
