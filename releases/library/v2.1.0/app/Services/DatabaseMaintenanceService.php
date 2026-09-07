<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DatabaseMaintenanceService
{
    public function backupDirectory(): string
    {
        return storage_path('app/database-backups');
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    public function createBackup(): array
    {
        File::ensureDirectoryExists($this->backupDirectory());

        $driver = (string) config('database.default');
        $filename = 'backup-'.now()->format('Y-m-d-His').'-'.Str::lower(Str::random(6));

        if ($driver === 'sqlite') {
            return $this->backupSqlite($filename);
        }

        if ($driver === 'mysql') {
            return $this->backupMysql($filename);
        }

        throw new \RuntimeException("Database backups are not supported for the [{$driver}] driver.");
    }

    /**
     * @return array{output: string, pending_migrations: list<string>}
     */
    public function runMigrations(): array
    {
        Artisan::call('migrate', ['--force' => true]);

        return [
            'output' => trim(Artisan::output()),
            'pending_migrations' => [],
        ];
    }

    public function restoreBackup(string $filename): void
    {
        $path = $this->resolveBackupPath($filename);

        if (! File::exists($path)) {
            throw new \RuntimeException('Backup file not found.');
        }

        $driver = (string) config('database.default');

        if ($driver === 'sqlite') {
            $this->restoreSqlite($path);

            return;
        }

        if ($driver === 'mysql') {
            $this->restoreMysql($path);

            return;
        }

        throw new \RuntimeException("Database restore is not supported for the [{$driver}] driver.");
    }

    /**
     * @return list<array{filename: string, size: int, size_label: string, created_at: string}>
     */
    public function listBackups(): array
    {
        if (! File::isDirectory($this->backupDirectory())) {
            return [];
        }

        return collect(File::files($this->backupDirectory()))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(function ($file) {
                $bytes = $file->getSize();

                return [
                    'filename' => $file->getFilename(),
                    'size' => $bytes,
                    'size_label' => $this->formatBytes($bytes),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('M d, Y h:i A'),
                ];
            })
            ->values()
            ->all();
    }

    public function deleteBackup(string $filename): void
    {
        $path = $this->resolveBackupPath($filename);
        File::delete($path);
    }

    public function resolveBackupPath(string $filename): string
    {
        $safeName = basename($filename);

        if ($safeName !== $filename || ! preg_match('/^backup-[A-Za-z0-9._-]+\.(sql|sqlite)$/', $safeName)) {
            throw new \RuntimeException('Invalid backup filename.');
        }

        return $this->backupDirectory().DIRECTORY_SEPARATOR.$safeName;
    }

    /**
     * @return array{driver: string, database: string, pending_migrations: int}
     */
    public function status(): array
    {
        $driver = (string) config('database.default');
        $connection = config('database.connections.'.$driver, []);
        $database = (string) ($connection['database'] ?? '');

        $pending = 0;

        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            $pending = count(array_diff(array_keys($files), $ran));
        } catch (\Throwable) {
            $pending = 0;
        }

        return [
            'driver' => $driver,
            'database' => $database,
            'pending_migrations' => $pending,
        ];
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    private function backupSqlite(string $filename): array
    {
        $database = (string) config('database.connections.sqlite.database');
        $target = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename.'.sqlite';

        if ($database !== ':memory:' && File::exists($database)) {
            File::copy($database, $target);

            return $this->formatBackupMeta($target);
        }

        $this->copySqliteConnection(DB::connection()->getPdo(), $target);

        return $this->formatBackupMeta($target);
    }

    private function copySqliteConnection(\PDO $source, string $targetPath): void
    {
        if (File::exists($targetPath)) {
            File::delete($targetPath);
        }

        $destination = new \PDO('sqlite:'.$targetPath);
        $destination->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $tables = $source->query(
            "SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $destination->exec((string) $table['sql']);

            $tableName = (string) $table['name'];
            $columns = $source->query('PRAGMA table_info('.$this->quoteSqliteIdentifier($tableName).')')
                ->fetchAll(\PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'name');

            if ($columnNames === []) {
                continue;
            }

            $quotedColumns = implode(', ', array_map(fn ($column) => $this->quoteSqliteIdentifier($column), $columnNames));
            $rows = $source->query('SELECT '.$quotedColumns.' FROM '.$this->quoteSqliteIdentifier($tableName));
            $insert = $destination->prepare(
                'INSERT INTO '.$this->quoteSqliteIdentifier($tableName).' ('.$quotedColumns.') VALUES ('.implode(', ', array_fill(0, count($columnNames), '?')).')'
            );

            while ($row = $rows->fetch(\PDO::FETCH_NUM)) {
                $insert->execute($row);
            }
        }

        $indexes = $source->query(
            "SELECT sql FROM sqlite_master WHERE type = 'index' AND sql IS NOT NULL AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($indexes as $indexSql) {
            $destination->exec((string) $indexSql);
        }
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    private function backupMysql(string $filename): array
    {
        $config = config('database.connections.mysql', []);
        $target = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename.'.sql';
        $mysqldump = $this->findMysqlBinary('mysqldump');

        $command = [
            $mysqldump,
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            '--result-file='.$target,
            '--single-transaction',
            '--routines',
            '--triggers',
            (string) ($config['database'] ?? ''),
        ];

        $process = new Process($command, null, [
            'MYSQL_PWD' => (string) ($config['password'] ?? ''),
        ]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'Backup failed.'));
        }

        return $this->formatBackupMeta($target);
    }

    private function restoreSqlite(string $path): void
    {
        $database = (string) config('database.connections.sqlite.database');
        DB::disconnect();
        File::copy($path, $database);
    }

    private function restoreMysql(string $path): void
    {
        $config = config('database.connections.mysql', []);
        $mysql = $this->findMysqlBinary('mysql');

        Schema::disableForeignKeyConstraints();

        $command = [
            $mysql,
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            (string) ($config['database'] ?? ''),
        ];

        $process = new Process($command, null, [
            'MYSQL_PWD' => (string) ($config['password'] ?? ''),
        ]);
        $process->setInput(File::get($path));
        $process->setTimeout(600);
        $process->run();

        Schema::enableForeignKeyConstraints();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput() ?: 'Restore failed.'));
        }
    }

    private function findMysqlBinary(string $binary): string
    {
        $candidates = array_filter([
            env('MYSQL_BIN_PATH') ? rtrim((string) env('MYSQL_BIN_PATH'), '\\/').DIRECTORY_SEPARATOR.$binary.($this->isWindows() ? '.exe' : '') : null,
            $this->isWindows() ? 'C:\\xampp\\mysql\\bin\\'.$binary.'.exe' : null,
            $binary,
            $binary.($this->isWindows() ? '.exe' : ''),
        ]);

        foreach ($candidates as $candidate) {
            $process = new Process([$candidate, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        throw new \RuntimeException("Could not find {$binary}. Install MySQL client tools or set MYSQL_BIN_PATH in .env.");
    }

    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string}
     */
    private function formatBackupMeta(string $path): array
    {
        $size = (int) File::size($path);

        return [
            'filename' => basename($path),
            'path' => $path,
            'size' => $size,
            'created_at' => now()->format('M d, Y h:i A'),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }
}
