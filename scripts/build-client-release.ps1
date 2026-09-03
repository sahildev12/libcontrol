# LibControl client release packager
# Usage: powershell -ExecutionPolicy Bypass -File scripts/build-client-release.ps1

$ErrorActionPreference = "Stop"

$Version = "1.0.0"
$Root = Split-Path -Parent $PSScriptRoot
$ReleaseDir = Join-Path $Root "releases\client\v$Version"
$StagingDir = Join-Path $ReleaseDir "staging"
$ZipPath = Join-Path $ReleaseDir "LibControl-client-v$Version.zip"

Write-Host "Building LibControl client release v$Version..."

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

    $sqlPath = Join-Path $ReleaseDir "LibControl-client-v$Version.sql"
    Write-Host "Exporting client database SQL..."
    php artisan LibControl:export-client-sql --output="$sqlPath" | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "SQL export failed. Check MySQL is running and mysqldump is available."
    }

    $appKey = (php artisan key:generate --show).Trim()
    $setupToken = -join ((48..57 + 65..90 + 97..122) | Get-Random -Count 32 | ForEach-Object { [char]$_ })

    $clientEnv = @"
APP_NAME=LibControl
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=https://your-domain.com
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
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="`${APP_NAME}"
MAIL_REPLY_TO_ADDRESS=
MAIL_REPLY_TO_NAME="`${APP_NAME}"

VITE_APP_NAME="`${APP_NAME}"

LIBCONTROL_PRODUCT_NAME=LibControl
LIBCONTROL_COMPANY_NAME=Phenomit
LIBCONTROL_COMPANY_URL=https://phenomit.com
LIBCONTROL_PRODUCT_BYLINE="LibControl is a product by Phenomit.com"

# Deployment licensing (required on client servers)
LIBCONTROL_LICENSE_KEY=your_license_key_from_phenomit
LIBCONTROL_SYNC_ENDPOINT=https://libcontrol.phenomit.com/api/runtime/sync
LIBCONTROL_LICENSE_GRACE_DAYS=7
LIBCONTROL_SYNC_INTERVAL=0
LIBCONTROL_LICENSE_SERVER=false

# Browser installer (no SSH) — keep token secret until install is done
LIBCONTROL_SETUP_TOKEN=$setupToken
LIBCONTROL_ADMIN_EMAIL=admin@your-domain.com
LIBCONTROL_ADMIN_PASSWORD=ChangeMeAfterLogin123!
LIBCONTROL_ADMIN_NAME=Library Admin
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

    # Remove dev-only files from staging
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

    # Clean runtime cache folders
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

    Set-Content -Path (Join-Path $StagingDir ".env") -Value $clientEnv -Encoding UTF8
    Set-Content -Path (Join-Path $StagingDir "VERSION") -Value $Version -Encoding UTF8
    Copy-Item -Path $sqlPath -Destination (Join-Path $StagingDir "database\LibControl-install.sql") -Force

    $installUrl = "https://your-domain.com/install?token=$setupToken"
    $installDoc = @"
# LibControl Client Release v$Version

## Package contents

Upload and extract ``LibControl-client-v$Version.zip`` to your hosting account.
Point your domain document root to the ``public`` folder inside the extracted app.

The zip also includes ``database/LibControl-install.sql`` for direct phpMyAdmin import.

## Before install

1. Create a MySQL database in your hosting panel.
2. Edit ``.env`` in the extracted folder (File Manager):
   - ``APP_URL`` - your live site URL (https)
   - ``DB_*`` - database name, user, password
   - ``LIBCONTROL_LICENSE_KEY`` - key from Phenomit
   - ``MAIL_*`` - SMTP settings (optional at first)
   - ``LIBCONTROL_ADMIN_EMAIL`` / ``LIBCONTROL_ADMIN_PASSWORD`` - first admin login

## Option A: Import SQL (recommended on shared hosting)

1. Open phpMyAdmin for the client database.
2. Import ``database/LibControl-install.sql`` (or the standalone ``LibControl-client-v$Version.sql`` from this release folder).
3. Create an empty file at ``storage/app/install.lock`` in File Manager.
4. Open the site and log in with the admin email/password from ``.env``.

Default seeded login (change in ``.env`` before import if you edit the SQL manually):

- Email: ``admin@your-domain.com``
- Password: ``ChangeMeAfterLogin123!``

## Option B: Browser installer (no SQL import)

Open this URL once in your browser (token is in your ``.env`` as ``LIBCONTROL_SETUP_TOKEN``):

    /install?token=$setupToken

Example after you set APP_URL:

    $installUrl

Click **Run installation**. This creates tables and your admin user.

## After install

- Log in with the admin email/password from ``.env``
- Change the admin password immediately
- Remove or keep ``LIBCONTROL_SETUP_TOKEN`` - installer is locked after success
- Do not enable ``LIBCONTROL_LICENSE_SERVER`` on client servers

## Support

Licensed installs phone home to:

    https://libcontrol.phenomit.com/api/runtime/sync

Contact Phenomit if the site shows an unlicensed error.
"@

    Set-Content -Path (Join-Path $ReleaseDir "INSTALL.md") -Value $installDoc -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "VERSION.txt") -Value $Version -Encoding UTF8
    Set-Content -Path (Join-Path $ReleaseDir "SETUP-TOKEN.txt") -Value $setupToken -Encoding UTF8

    if (Test-Path $ZipPath) {
        Remove-Item $ZipPath -Force
    }

    Write-Host "Creating zip archive (this may take a minute)..."
    Compress-Archive -Path (Join-Path $StagingDir "*") -DestinationPath $ZipPath -CompressionLevel Optimal

    Remove-Item $StagingDir -Recurse -Force

    Write-Host ""
    Write-Host "Done."
    Write-Host "Zip: $ZipPath"
    Write-Host "SQL: $sqlPath"
    Write-Host "Install token (also in SETUP-TOKEN.txt): $setupToken"
}
finally {
    Pop-Location
}
