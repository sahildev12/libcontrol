# LibControl platform (landlord) release packager
# Usage: powershell -ExecutionPolicy Bypass -File scripts/build-platform-release.ps1

$ErrorActionPreference = "Stop"

$Version = "2.0.0"
$Root = Split-Path -Parent $PSScriptRoot
$ReleaseDir = Join-Path $Root "releases\platform\v$Version"
$StagingDir = Join-Path $ReleaseDir "staging"
$ZipPath = Join-Path $ReleaseDir "LibControl-platform-v$Version.zip"

Write-Host "Building LibControl platform release v$Version..."

if (Test-Path $StagingDir) {
    Remove-Item $StagingDir -Recurse -Force
}
New-Item -ItemType Directory -Path $StagingDir -Force | Out-Null

Push-Location $Root
try {
    Write-Host "Installing production PHP dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction | Out-Host

    Write-Host "Building frontend assets..."
    npm run build | Out-Host

    $appKey = (php artisan key:generate --show).Trim()

    $platformEnv = @"
APP_NAME=LibControl
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=https://libcontrol.phenomit.com
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
DB_DATABASE=your_landlord_database
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

LIBCONTROL_LICENSE_SERVER=true
LIBCONTROL_TENANCY_ENABLED=true
LIBCONTROL_TENANT_BASE_DOMAIN=phenomit.com
LIBCONTROL_TENANT_LANDLORD_HOSTS=libcontrol.phenomit.com

LIBCONTROL_ADMIN_EMAIL=developer@your-domain.com
LIBCONTROL_ADMIN_PASSWORD=ChangeMeAfterLogin123!
LIBCONTROL_ADMIN_NAME=Admin
"@

    $excludeDirs = @(
        ".git",
        "node_modules",
        "libcontrol-website",
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

        $destination = Join-Path $StagingDir $item.Name
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
        $path = Join-Path $StagingDir $file
        if (Test-Path $path) {
            Remove-Item $path -Force
        }
    }

    $runtimePaths = @(
        "storage\logs",
        "storage\framework\cache\data",
        "storage\framework\sessions",
        "storage\framework\views"
    )
    foreach ($runtimePath in $runtimePaths) {
        $fullPath = Join-Path $StagingDir $runtimePath
        if (Test-Path $fullPath) {
            Get-ChildItem $fullPath -Force | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
        }
    }

    Set-Content -Path (Join-Path $StagingDir ".env") -Value $platformEnv -Encoding UTF8
    Set-Content -Path (Join-Path $StagingDir "VERSION") -Value $Version -Encoding UTF8

    $installDoc = @"
# LibControl Platform Release v$Version

## What this is

This zip is for your **main LibControl server** (landlord), e.g. libcontrol.phenomit.com.
It supports subdomain multi-tenancy: each client gets clientname.phenomit.com with its own database.

## Upload steps

1. Upload and extract this zip on your hosting.
2. Point libcontrol.phenomit.com to the ``public`` folder.
3. Point wildcard DNS ``*.phenomit.com`` to the same hosting.
4. Open ``https://libcontrol.phenomit.com/setup`` if not installed yet.
5. Enter landlord database details and click **Prepare database (auto-migrate)**.
6. Finish setup and log in at ``/admin/login``.

## Adding a client library

1. Create an empty MySQL database in Hostinger for the client.
2. Developer panel -> **Client Libraries** -> **Add client library**.
3. Enter subdomain + database name.
4. Click **Prepare database (auto-migrate)**.
5. Submit the form to create the client admin login.

Client URL example: ``https://dise.phenomit.com/admin/login``

## Do not use for

- Separate client-owned servers (deprecated)
- Client zip uploads to third-party hosting
"@

    Set-Content -Path (Join-Path $ReleaseDir "INSTALL.md") -Value $installDoc -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "VERSION.txt") -Value $Version -Encoding UTF8

    if (Test-Path $ZipPath) {
        Remove-Item $ZipPath -Force
    }

    Write-Host "Creating zip archive..."
    Compress-Archive -Path (Join-Path $StagingDir "*") -DestinationPath $ZipPath -CompressionLevel Optimal
    Remove-Item $StagingDir -Recurse -Force

    Write-Host ""
    Write-Host "Done."
    Write-Host "Zip: $ZipPath"
}
finally {
    Pop-Location
}
