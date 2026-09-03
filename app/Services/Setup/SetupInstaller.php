<?php

namespace App\Services\Setup;

use App\Support\Runtime\SyncCoordinator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SetupInstaller
{
    /**
     * @return array<string, mixed>
     */
    public function requirements(): array
    {
        $paths = [
            base_path('storage'),
            base_path('bootstrap/cache'),
            storage_path('framework'),
            storage_path('logs'),
        ];

        $writable = collect($paths)->every(fn (string $path) => is_dir($path) && is_writable($path));

        return [
            'php_version' => PHP_VERSION,
            'php_ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'tokenizer' => extension_loaded('tokenizer'),
                'json' => extension_loaded('json'),
            ],
            'writable_paths' => $writable,
            'env_writable' => ! File::exists(base_path('.env')) || is_writable(base_path('.env')),
        ];
    }

    public function isInstalled(): bool
    {
        return File::exists(storage_path('app/install.lock'));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function testAndMigrateDatabase(array $input): void
    {
        $this->applyDatabaseConfig($input);
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::connection('mysql')->getPdo();

        Artisan::call('migrate', ['--force' => true]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function install(array $input): void
    {
        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $appUrl = rtrim((string) ($input['app_url'] ?? ''), '/');
        $timezone = (string) ($input['timezone'] ?? 'Asia/Kolkata');
        $tenancyEnabled = filter_var($input['tenancy_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $baseDomain = (string) ($input['tenant_base_domain'] ?? 'phenomit.com');
        $landlordHosts = (string) ($input['tenant_landlord_hosts'] ?? parse_url($appUrl, PHP_URL_HOST) ?: 'localhost');
        $appName = (string) ($input['app_name'] ?? 'LibControl');
        $developerEmail = 'developer@'.(parse_url($appUrl, PHP_URL_HOST) ?: 'localhost');
        $developerPassword = Str::password(32);

        $env = $this->buildEnv([
            'APP_NAME' => $appName,
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
            'APP_TIMEZONE' => $timezone,
            'DB_HOST' => (string) ($input['db_host'] ?? '127.0.0.1'),
            'DB_PORT' => (string) ($input['db_port'] ?? '3306'),
            'DB_DATABASE' => (string) ($input['db_database'] ?? ''),
            'DB_USERNAME' => (string) ($input['db_username'] ?? ''),
            'DB_PASSWORD' => (string) ($input['db_password'] ?? ''),
            'LIBCONTROL_LICENSE_SERVER' => $tenancyEnabled ? 'true' : 'false',
            'LIBCONTROL_TENANCY_ENABLED' => $tenancyEnabled ? 'true' : 'false',
            'LIBCONTROL_TENANT_BASE_DOMAIN' => $baseDomain,
            'LIBCONTROL_TENANT_LANDLORD_HOSTS' => $landlordHosts,
            'LIBCONTROL_PRODUCT_NAME' => $appName,
            'LIBCONTROL_DEVELOPER_EMAIL' => $developerEmail,
            'LIBCONTROL_DEVELOPER_PASSWORD' => $developerPassword,
            'LIBCONTROL_ADMIN_EMAIL' => (string) ($input['admin_email'] ?? ''),
            'LIBCONTROL_ADMIN_PASSWORD' => (string) ($input['admin_password'] ?? ''),
            'LIBCONTROL_ADMIN_NAME' => 'Admin',
        ]);

        config(['app.key' => $appKey]);
        $this->applyDatabaseConfig($input);
        config([
            'libcontrol.install.product_name' => $appName,
            'libcontrol.install.developer_email' => $developerEmail,
            'libcontrol.install.developer_password' => $developerPassword,
            'libcontrol.install.admin_email' => (string) ($input['admin_email'] ?? ''),
            'libcontrol.install.admin_password' => (string) ($input['admin_password'] ?? ''),
            'libcontrol.install.admin_name' => 'Admin',
        ]);

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => 'DeveloperInstallSeeder', '--force' => true]);

        File::ensureDirectoryExists(storage_path('app'));
        File::put(storage_path('app/install.lock'), now()->toIso8601String());

        if (! config('libcontrol.license_server.enabled')) {
            try {
                app(SyncCoordinator::class)->sync(setupComplete: true);
            } catch (\Throwable) {
                // Discovery ping is best-effort during setup.
            }
        }

        // Defer .env write until after the HTTP response is sent. `php artisan serve`
        // restarts when .env changes, which would drop the connection mid-install.
        app()->terminating(function () use ($env): void {
            File::put(base_path('.env'), $env);

            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
        });
    }

    /**
     * @param  array<string, string>  $values
     */
    private function buildEnv(array $values, bool $installed = true): string
    {
        $sessionDriver = $installed ? 'database' : 'file';
        $cacheStore = $installed ? 'database' : 'file';
        $queueConnection = $installed ? 'database' : 'sync';

        return <<<ENV
APP_NAME={$this->quoteEnvValue($values['APP_NAME'])}
APP_ENV={$values['APP_ENV']}
APP_KEY={$values['APP_KEY']}
APP_DEBUG={$values['APP_DEBUG']}
APP_URL={$values['APP_URL']}
APP_TIMEZONE={$values['APP_TIMEZONE']}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$values['DB_HOST']}
DB_PORT={$values['DB_PORT']}
DB_DATABASE={$values['DB_DATABASE']}
DB_USERNAME={$values['DB_USERNAME']}
        DB_PASSWORD={$this->quoteEnvValue($values['DB_PASSWORD'] ?? '', alwaysQuote: true)}

SESSION_DRIVER={$sessionDriver}
BROADCAST_CONNECTION=log
QUEUE_CONNECTION={$queueConnection}
CACHE_STORE={$cacheStore}

MAIL_MAILER=log

LIBCONTROL_LICENSE_SERVER={$values['LIBCONTROL_LICENSE_SERVER']}
LIBCONTROL_TENANCY_ENABLED={$values['LIBCONTROL_TENANCY_ENABLED']}
LIBCONTROL_TENANT_BASE_DOMAIN={$values['LIBCONTROL_TENANT_BASE_DOMAIN']}
LIBCONTROL_TENANT_LANDLORD_HOSTS={$values['LIBCONTROL_TENANT_LANDLORD_HOSTS']}

LIBCONTROL_PRODUCT_NAME={$values['LIBCONTROL_PRODUCT_NAME']}
LIBCONTROL_DEVELOPER_EMAIL={$values['LIBCONTROL_DEVELOPER_EMAIL']}
LIBCONTROL_DEVELOPER_PASSWORD={$this->quoteEnvValue($values['LIBCONTROL_DEVELOPER_PASSWORD'], alwaysQuote: true)}

LIBCONTROL_ADMIN_EMAIL={$values['LIBCONTROL_ADMIN_EMAIL']}
LIBCONTROL_ADMIN_PASSWORD={$this->quoteEnvValue($values['LIBCONTROL_ADMIN_PASSWORD'], alwaysQuote: true)}
LIBCONTROL_ADMIN_NAME={$values['LIBCONTROL_ADMIN_NAME']}

ENV;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function applyDatabaseConfig(array $input): void
    {
        config([
            'database.connections.mysql.host' => (string) ($input['db_host'] ?? '127.0.0.1'),
            'database.connections.mysql.port' => (string) ($input['db_port'] ?? '3306'),
            'database.connections.mysql.database' => (string) ($input['db_database'] ?? ''),
            'database.connections.mysql.username' => (string) ($input['db_username'] ?? ''),
            'database.connections.mysql.password' => (string) ($input['db_password'] ?? ''),
        ]);
    }

    private function quoteEnvValue(string $value, bool $alwaysQuote = false): string
    {
        if ($value === '') {
            return '';
        }

        if ($alwaysQuote || preg_match('/[\s#="\']/', $value) || preg_match('/[&>|()`\\\\,;~<]/', $value)) {
            return '"'.addcslashes($value, '"\\').'"';
        }

        return $value;
    }
}
