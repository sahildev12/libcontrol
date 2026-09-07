# Build a distributable Attendance addon ZIP for client installs.
# Usage: powershell -ExecutionPolicy Bypass -File scripts/build-attendance-addon-zip.ps1

$ErrorActionPreference = "Stop"

$Version = "1.0.0"
$Root = Split-Path -Parent $PSScriptRoot
$Slug = "attendance"
$BuildDir = Join-Path $Root "addons\$Slug\build"
$ZipPath = Join-Path $Root "addons\$Slug\LibControl-addon-$Slug-v$Version.zip"

Write-Host "Building Attendance addon ZIP v$Version..."

if (Test-Path $BuildDir) {
    Remove-Item $BuildDir -Recurse -Force
}
New-Item -ItemType Directory -Path $BuildDir -Force | Out-Null

Copy-Item (Join-Path $Root "addons\$Slug\addon.json") (Join-Path $BuildDir "addon.json") -Force

$paths = @(
    "app\Addons\Attendance",
    "database\migrations\addons\attendance",
    "resources\views\attendance"
)

foreach ($relativePath in $paths) {
    $source = Join-Path $Root $relativePath
    if (-not (Test-Path $source)) {
        throw "Missing addon source path: $relativePath"
    }

    $destination = Join-Path $BuildDir $relativePath
  New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
    Copy-Item -Path $source -Destination $destination -Recurse -Force
}

if (Test-Path $ZipPath) {
    Remove-Item $ZipPath -Force
}

Compress-Archive -Path (Join-Path $BuildDir "*") -DestinationPath $ZipPath -CompressionLevel Optimal
Remove-Item $BuildDir -Recurse -Force

Write-Host "Done."
Write-Host "Zip: $ZipPath"
