# LibControl library (client) release packager
# Usage:
#   powershell -ExecutionPolicy Bypass -File scripts/build-library-release.ps1
#   powershell -ExecutionPolicy Bypass -File scripts/build-library-release.ps1 -Zip

param(
    [string]$Version = "2.1.2",
    [switch]$Zip
)

$ErrorActionPreference = "Stop"

$Root = Split-Path -Parent $PSScriptRoot
$ReleaseDir = Join-Path $Root "releases\library\v$Version"
$ZipPath = Join-Path $ReleaseDir "LibControl-library-v$Version.zip"

Write-Host "Building LibControl library release v$Version..."

if (Test-Path $ReleaseDir) {
    Get-ChildItem $ReleaseDir -Force | Remove-Item -Recurse -Force
}
New-Item -ItemType Directory -Path $ReleaseDir -Force | Out-Null

Push-Location $Root
try {
    Write-Host "Installing production PHP dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction | Out-Host

    Write-Host "Building frontend assets..."
    npm run build | Out-Host

    $appKey = (php artisan key:generate --show).Trim()

    $libraryEnv = @"
APP_NAME=LibControl
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=https://dise.phenomit.com
APP_TIMEZONE=Asia/Kolkata

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_library_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=file
BROADCAST_CONNECTION=log
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="`${APP_NAME}"

LIBCONTROL_LICENSE_SERVER=false
LIBCONTROL_TENANCY_ENABLED=false
LIBCONTROL_SYNC_ENDPOINT=https://libcontrol.phenomit.com/api/runtime/sync

LIBCONTROL_ADMIN_EMAIL=admin@your-domain.com
LIBCONTROL_ADMIN_PASSWORD=ChangeMeAfterLogin123!
LIBCONTROL_ADMIN_NAME=Admin

# Optional: path to MySQL bin folder for database backups on Windows/XAMPP (e.g. C:\xampp\mysql\bin)
# MYSQL_BIN_PATH=
"@

    $excludeDirs = @(
        ".git",
        "node_modules",
        "libcontrol-website",
        "mobile",
        "tests",
        "releases",
        ".cursor"
    )

    $items = Get-ChildItem -Path $Root -Force
    foreach ($item in $items) {
        if ($excludeDirs -contains $item.Name) {
            continue
        }

        if ($item.Name -eq "scripts" -and $item.PSIsContainer) {
            continue
        }

        $destination = Join-Path $ReleaseDir $item.Name
        if ($item.PSIsContainer) {
            Copy-Item -Path $item.FullName -Destination $destination -Recurse -Force
        } else {
            Copy-Item -Path $item.FullName -Destination $destination -Force
        }
    }

    $devFiles = @(
        ".env",
        ".env.example",
        "phpunit.xml",
        ".editorconfig",
        "package.json",
        "package-lock.json",
        "vite.config.js",
        "postcss.config.js",
        "tailwind.config.js"
    )
    foreach ($file in $devFiles) {
        $path = Join-Path $ReleaseDir $file
        if (Test-Path $path) {
            Remove-Item $path -Force
        }
    }

    $landlordOnlyPaths = @(
        "routes\developer.php",
        "routes\license-server.php",
        "routes\tenants.php",
        "app\Http\Controllers\Developer\DeploymentController.php",
        "app\Http\Controllers\Developer\TenantController.php",
        "app\Http\Controllers\Api\RuntimeSyncController.php",
        "app\Http\Requests\StoreLicensedDeploymentRequest.php",
        "app\Http\Requests\UpdateLicensedDeploymentRequest.php",
        "app\Http\Requests\StoreTenantRequest.php",
        "app\Http\Requests\UpdateTenantRequest.php",
        "app\Services\Tenancy\TenantProvisioner.php",
        "resources\views\developer"
    )

    foreach ($relativePath in $landlordOnlyPaths) {
        $fullPath = Join-Path $ReleaseDir $relativePath
        if (Test-Path $fullPath) {
            Remove-Item $fullPath -Recurse -Force
        }
    }

    $addonBundlePaths = @(
        "app\Addons",
        "database\migrations\addons",
        "resources\views\attendance",
        "addons"
    )

    foreach ($relativePath in $addonBundlePaths) {
        $fullPath = Join-Path $ReleaseDir $relativePath
        if (Test-Path $fullPath) {
            Remove-Item $fullPath -Recurse -Force
        }
    }

    Set-Content -Path (Join-Path $ReleaseDir "config\addons.php") -Value @"
<?php

return [];
"@ -Encoding UTF8

    Copy-Item -Path (Join-Path $Root "config\admin-nav-library.php") -Destination (Join-Path $ReleaseDir "config\admin-nav.php") -Force

    $webRoutesPath = Join-Path $ReleaseDir "routes\web.php"
    $webRoutes = Get-Content $webRoutesPath -Raw
    $webRoutes = $webRoutes -replace "require __DIR__\.'\/license-server\.php';\r?\n", ""
    $webRoutes = $webRoutes -replace "require __DIR__\.'\/developer\.php';\r?\n", ""
    $webRoutes = $webRoutes -replace "require __DIR__\.'\/tenants\.php';\r?\n", ""
    Set-Content -Path $webRoutesPath -Value $webRoutes -Encoding UTF8

    $runtimePaths = @(
        "storage\logs",
        "storage\framework\cache\data",
        "storage\framework\sessions",
        "storage\framework\views"
    )
    foreach ($runtimePath in $runtimePaths) {
        $fullPath = Join-Path $ReleaseDir $runtimePath
        if (Test-Path $fullPath) {
            Get-ChildItem $fullPath -Force | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
        }
    }

    Set-Content -Path (Join-Path $ReleaseDir ".env") -Value $libraryEnv -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "VERSION") -Value $Version -Encoding UTF8

    $installDoc = @"
# LibControl Library Release v$Version

## What this is

This folder is for a **single client library** (e.g. dise.phenomit.com).
It does **not** include Dev & Domains or Client Libraries admin tools.

After setup, this domain automatically pings libcontrol.phenomit.com so Phenomit can see the new installation.

## Upload steps

1. Upload this folder to your hosting (e.g. dise.phenomit.com).
2. Point the domain to the ``public`` folder.
3. Open ``https://dise.phenomit.com/setup``.
4. Set **App name** to your library name (e.g. Dise).
5. Enter database details and click **Prepare database (auto-migrate)**.
6. Enter your **admin email and password** and click **Install LibControl**.
7. Log in at ``/admin/login``.

## Updating an existing client

1. Back up the database and ``.env`` file first.
2. Upload changed files over the existing install (keep the client's ``.env``).
3. Run migrations from **Settings → Database** or ``php artisan migrate --force`` on the server.

## Phenomit visibility

When install finishes, a discovery ping is sent to:

``https://libcontrol.phenomit.com/api/runtime/sync``

You will see the domain under **Dev & Domains -> Live installations** on libcontrol.phenomit.com (developer login).

## Not included

- Dev & Domains UI
- Client Libraries (multi-tenant landlord tools)
- License server API endpoints
- Addons (install separately from ``releases/library/addon/``)
"@

    Set-Content -Path (Join-Path $ReleaseDir "INSTALL.md") -Value $installDoc -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "VERSION.txt") -Value $Version -Encoding UTF8

    Write-Host ""
    Write-Host "Done."
    Write-Host "Release folder: $ReleaseDir"

    if ($Zip) {
        if (Test-Path $ZipPath) {
            Remove-Item $ZipPath -Force
        }

        Write-Host "Creating zip archive..."
        Compress-Archive -Path (Join-Path $ReleaseDir "*") -DestinationPath $ZipPath -CompressionLevel Optimal
        Write-Host "Zip: $ZipPath"
    }
}
finally {
    Pop-Location
}
