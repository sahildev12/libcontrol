# Deploy libcontrol.in hub + demo.libcontrol.in from origin/main.
# Requires: SSH access to Hostinger (password or key). Run from repo root after `npm run build`.
$ErrorActionPreference = "Stop"

$sshHost = "u843330709@45.130.228.59"
$sshPort = 65002
$hub = "/home/u843330709/domains/libcontrol.in/public_html"
$demo = "$hub/demo"
$localBuild = Join-Path $PSScriptRoot "..\public\build" | Resolve-Path

if (-not (Test-Path (Join-Path $localBuild "manifest.json"))) {
    throw "Run npm run build first (missing public/build/manifest.json)."
}

$repoRoot = Join-Path $PSScriptRoot ".." | Resolve-Path
$mailEnv = Join-Path $repoRoot "deploy\mail.env"
$mailSettingsLocal = Join-Path $env:TEMP "libcontrol-mail-settings.php"

if (Test-Path $mailEnv) {
    Write-Host "Building mail-settings.php from deploy/mail.env..."
    $mailPhp = php (Join-Path $repoRoot "scripts\build-mail-settings-php.php") $mailEnv
    [System.IO.File]::WriteAllText($mailSettingsLocal, $mailPhp)
} else {
    Write-Warning 'deploy/mail.env not found. Copy deploy/mail.env.example to deploy/mail.env and re-run deploy to enable SMTP.'
}

$remoteDeploy = @"
set -e
for APP in '$hub' '$demo'; do
  echo \"=== Deploying \$APP ===\"
  cd \"\$APP\"
  git fetch origin && git reset --hard origin/main
  composer install --no-dev --optimize-autoloader --no-interaction
  php artisan migrate --force
  bash scripts/ensure-marketing-public-assets-link.sh
  php artisan optimize
done
"@

Write-Host "Pulling origin/main on hub and demo..."
ssh -p $sshPort $sshHost $remoteDeploy

Write-Host "Uploading Vite build to hub and demo..."
scp -P $sshPort -r "$localBuild\*" "${sshHost}:${hub}/public/build/"
scp -P $sshPort -r "$localBuild\*" "${sshHost}:${demo}/public/build/"

if (Test-Path $mailSettingsLocal) {
    Write-Host "Uploading mail-settings.php to hub and demo..."
    foreach ($app in @($hub, $demo)) {
        ssh -p $sshPort $sshHost "mkdir -p ${app}/storage/app/private"
        scp -P $sshPort $mailSettingsLocal "${sshHost}:${app}/storage/app/private/mail-settings.php"
    }
}

Write-Host "Done. Hub: https://libcontrol.in/  Demo: https://demo.libcontrol.in/"
