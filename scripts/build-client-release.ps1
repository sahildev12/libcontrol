# LibControl client release packager
# Usage:
#   powershell -ExecutionPolicy Bypass -File scripts/build-client-release.ps1 -ClientSlug aims
#   powershell -ExecutionPolicy Bypass -File scripts/build-client-release.ps1 -ClientSlug aims -ClientName "Aims Library" -AppUrl "https://aims.phenomit.com"

param(
    [Parameter(Mandatory = $true)]
    [string]$ClientSlug,

    [string]$ClientName = "",
    [string]$AppUrl = "",
    [string]$Version = "2.1.4",
    [string]$StudentCodePrefix = "",
    [string]$AdminEmail = "",
    [string]$AdminPassword = "ChangeMeAfterLogin123!",
    [string]$AdminName = "Library Admin",
    [string]$DbName = "",
    [string]$DbUser = "",
    [string]$DbPassword = "",
    [string]$MailFrom = "",
    [string]$LicenseKey = "your_license_key_from_phenomit",
    [switch]$SkipZip,
    [switch]$SkipSql
)

$ErrorActionPreference = "Stop"

function Write-Utf8NoBom {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Value
    )

    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Value, $encoding)
}

$Slug = $ClientSlug.Trim().ToLower()
if ($Slug -notmatch '^[a-z0-9-]+$') {
    throw "ClientSlug must contain only lowercase letters, numbers, and hyphens."
}

if ([string]::IsNullOrWhiteSpace($ClientName)) {
    $pretty = ($Slug -split '-' | ForEach-Object {
        if ($_.Length -eq 0) { return }
        $_.Substring(0, 1).ToUpper() + $_.Substring(1)
    }) -join ' '
    $ClientName = "$pretty Library"
}

if ([string]::IsNullOrWhiteSpace($AppUrl)) {
    $AppUrl = "https://$Slug.phenomit.com"
}

if ([string]::IsNullOrWhiteSpace($StudentCodePrefix)) {
    $StudentCodePrefix = ($Slug -replace '[^a-z0-9]', '').ToUpper()
    if ($StudentCodePrefix.Length -gt 6) {
        $StudentCodePrefix = $StudentCodePrefix.Substring(0, 6)
    }
    if ($StudentCodePrefix.Length -lt 2) {
        $StudentCodePrefix = "LIB"
    }
}

if ([string]::IsNullOrWhiteSpace($AdminEmail)) {
    $AdminEmail = "admin@$Slug.phenomit.com"
}

if ([string]::IsNullOrWhiteSpace($DbName)) {
    $DbName = "${Slug}_libcontrol"
}

if ([string]::IsNullOrWhiteSpace($DbUser)) {
    $DbUser = $DbName
}

if ([string]::IsNullOrWhiteSpace($MailFrom)) {
    $MailFrom = $AdminEmail
}

$Root = Split-Path -Parent $PSScriptRoot
$ReleaseDir = Join-Path $Root "releases\client\$Slug\v$Version"
$StagingDir = Join-Path $ReleaseDir "staging"
$ZipPath = Join-Path $ReleaseDir "LibControl-$Slug-v$Version.zip"
$SqlPath = Join-Path $ReleaseDir "LibControl-$Slug-v$Version.sql"

Write-Host "Building LibControl client release for [$ClientName] ($Slug) v$Version..."

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

    if ($SkipSql) {
        Write-Host "Skipping SQL export (-SkipSql). Use for in-place client updates; run migrations on the server."
    } else {
        Write-Host "Exporting client database SQL..."
        $exportArgs = @(
            "LibControl:export-client-sql",
            "--output=$SqlPath",
            "--admin-email=$AdminEmail",
            "--admin-password=$AdminPassword",
            "--admin-name=$AdminName",
            "--display-name=$ClientName",
            "--student-code-prefix=$StudentCodePrefix"
        )
        php artisan @exportArgs | Out-Host
        if ($LASTEXITCODE -ne 0) {
            throw "SQL export failed. Check MySQL is running and mysqldump is available."
        }
    }

    $appKey = (php artisan key:generate --show).Trim()
    $setupToken = -join ((48..57 + 65..90 + 97..122) | Get-Random -Count 32 | ForEach-Object { [char]$_ })

    $clientEnv = @"
APP_NAME="$ClientName"
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=$AppUrl
APP_TIMEZONE=Asia/Kolkata

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=$DbName
DB_USERNAME=$DbUser
DB_PASSWORD=$DbPassword

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="$MailFrom"
MAIL_FROM_NAME="`${APP_NAME}"
MAIL_REPLY_TO_ADDRESS="$MailFrom"
MAIL_REPLY_TO_NAME="`${APP_NAME}"

VITE_APP_NAME="`${APP_NAME}"

LIBCONTROL_PRODUCT_NAME="$ClientName"
LIBCONTROL_COMPANY_NAME=Phenomit
LIBCONTROL_COMPANY_URL=https://phenomit.com
LIBCONTROL_PRODUCT_BYLINE="LibControl is a product by Phenomit.com"

LIBCONTROL_CLIENT_NAME="$ClientName"
LIBCONTROL_DEPLOYMENT_SLUG=$Slug
LIBCONTROL_LIBRARY_STYLE=standard
LIBCONTROL_BASELINE_VERSION=$Version
LIBCONTROL_CLIENT_MAINTAINER=Phenomit
LIBCONTROL_INSTALL_STUDENT_CODE_PREFIX=$StudentCodePrefix

LIBCONTROL_LICENSE_KEY=$LicenseKey
LIBCONTROL_SYNC_ENDPOINT=https://libcontrol.phenomit.com/api/runtime/sync
LIBCONTROL_PUBLIC_URL=$AppUrl
LIBCONTROL_LICENSE_GRACE_DAYS=7
LIBCONTROL_SYNC_INTERVAL=0
LIBCONTROL_LICENSE_SERVER=false
LIBCONTROL_TENANCY_ENABLED=false
LIBCONTROL_SEED_DEMO=false

LIBCONTROL_SUPPORT_EMAIL=support@phenomit.com
LIBCONTROL_SUPPORT_PHONE=

LIBCONTROL_SETUP_TOKEN=$setupToken
LIBCONTROL_ADMIN_EMAIL=$AdminEmail
LIBCONTROL_ADMIN_PASSWORD=$AdminPassword
LIBCONTROL_ADMIN_NAME=$AdminName
"@

    $excludeDirs = @(
        ".git",
        "node_modules",
        "libcontrol-website",
        "libspace-website",
        "tests",
        "releases",
        ".cursor",
        "mobile"
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

    $customizationPath = Join-Path $StagingDir "config\libcontrol-customization.php"
    if (Test-Path $customizationPath) {
        $customization = Get-Content $customizationPath -Raw
        $customization = $customization -replace "'client_name' => env\('LIBCONTROL_CLIENT_NAME', '[^']*'\)", "'client_name' => env('LIBCONTROL_CLIENT_NAME', '$ClientName')"
        $customization = $customization -replace "'deployment_slug' => env\('LIBCONTROL_DEPLOYMENT_SLUG', '[^']*'\)", "'deployment_slug' => env('LIBCONTROL_DEPLOYMENT_SLUG', '$Slug')"
        $customization = $customization -replace "'baseline_version' => env\('LIBCONTROL_BASELINE_VERSION', '[^']*'\)", "'baseline_version' => env('LIBCONTROL_BASELINE_VERSION', '$Version')"
        $customization = $customization -replace "'summary' => '[^']*'", "'summary' => 'Production deployment for $ClientName.'"
        Set-Content -Path $customizationPath -Value $customization -Encoding UTF8
    }

    $htaccessSource = Join-Path $Root "deploy\client-root.htaccess"
    if (-not (Test-Path $htaccessSource)) {
        throw "Missing deploy/client-root.htaccess - required for all client release zips."
    }
    Copy-Item -Path $htaccessSource -Destination (Join-Path $StagingDir ".htaccess") -Force

    Write-Utf8NoBom -Path (Join-Path $StagingDir ".env") -Value $clientEnv
    Set-Content -Path (Join-Path $StagingDir "VERSION") -Value $Version -Encoding UTF8
    if (-not $SkipSql) {
        Copy-Item -Path $SqlPath -Destination (Join-Path $StagingDir "database\LibControl-install.sql") -Force
    }
    Write-Utf8NoBom -Path (Join-Path $ReleaseDir ".env.example") -Value $clientEnv

    $installUrl = "$AppUrl/install?token=$setupToken"
    $installDoc = @"
# LibControl Client Release — $ClientName

**Slug:** ``$Slug``  
**Version:** ``$Version``  
**URL:** ``$AppUrl``

## Package contents

- ``LibControl-$Slug-v$Version.zip`` — upload to hosting (document root = extracted app folder; root ``.htaccess`` routes to ``public/``)
- ``LibControl-$Slug-v$Version.sql`` — phpMyAdmin import
- ``.env.example`` — client environment template (also inside the zip as ``.env``)

## Client .env highlights

| Setting | Value |
|---------|-------|
| APP_NAME | $ClientName |
| APP_URL | $AppUrl |
| DB_DATABASE | $DbName |
| LIBCONTROL_CLIENT_NAME | $ClientName |
| LIBCONTROL_DEPLOYMENT_SLUG | $Slug |
| LIBCONTROL_INSTALL_STUDENT_CODE_PREFIX | $StudentCodePrefix |
| LIBCONTROL_PUBLIC_URL | $AppUrl |
| LIBCONTROL_ADMIN_EMAIL | $AdminEmail |

## Before go-live

1. Create MySQL database ``$DbName`` on Hostinger.
2. Edit ``.env`` after upload:
   - ``DB_USERNAME`` / ``DB_PASSWORD``
   - ``LIBCONTROL_LICENSE_KEY`` from Phenomit
   - ``MAIL_USERNAME`` / ``MAIL_PASSWORD`` (quote passwords containing ``#``)
3. Import ``database/LibControl-install.sql`` in phpMyAdmin **or** open the browser installer.

## Default admin login (from SQL seed)

- Email: ``$AdminEmail``
- Password: ``$AdminPassword``

Change the password immediately after first login.

## Browser installer (alternative)

``$installUrl``

Token is also in ``LIBCONTROL_SETUP_TOKEN`` inside ``.env``.

## After install

- Run **Settings → Database → Run migrations** when updating an existing install.
- Sync library to Phenomit: **Settings → Developer → Sync library to Phenomit**
- Do **not** enable ``LIBCONTROL_LICENSE_SERVER`` on client servers.
"@

    Set-Content -Path (Join-Path $ReleaseDir "INSTALL.md") -Value $installDoc -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "VERSION.txt") -Value $Version -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "SETUP-TOKEN.txt") -Value $setupToken -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "CLIENT.txt") -Value "$ClientName`n$Slug`n$AppUrl" -Encoding UTF8

    Write-Host ""
    Write-Host "Done."
    Write-Host "Client: $ClientName ($Slug)"
    Write-Host "Code: $(Join-Path $ReleaseDir 'staging')"
    Write-Host "Env:  $(Join-Path $ReleaseDir '.env.example')"
    Write-Host "Install token (also in SETUP-TOKEN.txt): $setupToken"

    if ($SkipSql) {
        Write-Host "SQL:  (skipped - run php artisan migrate --force on the live server)"
    } else {
        Write-Host "SQL:  $SqlPath"
    }

    if ($SkipZip) {
        Write-Host "Zip:  (skipped - upload the staging folder or zip it yourself)"
        return
    }

    if (Test-Path $ZipPath) {
        Remove-Item $ZipPath -Force
    }

    Write-Host "Creating zip archive (this may take a minute)..."
    Compress-Archive -Path (Join-Path $StagingDir "*") -DestinationPath $ZipPath -CompressionLevel Optimal
    Remove-Item $StagingDir -Recurse -Force
    Write-Host "Zip:  $ZipPath"
}
finally {
    Pop-Location
}
