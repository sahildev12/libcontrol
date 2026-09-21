# Build the Aims Library client release package.
# Default: staging folder only (no zip, no SQL) — for updating an existing aims.phenomit.com install.
# Fresh install with SQL + zip:
#   powershell -ExecutionPolicy Bypass -File scripts/build-aims-release.ps1 -WithZip -WithSql
param(
    [switch]$WithZip,
    [switch]$WithSql
)

$clientScript = Join-Path $PSScriptRoot "build-client-release.ps1"
$invokeArgs = @(
    "-ClientSlug", "aims",
    "-ClientName", "Aims Library",
    "-AppUrl", "https://aims.phenomit.com",
    "-Version", "2.1.5",
    "-StudentCodePrefix", "AIMS",
    "-AdminEmail", "admin@aims.phenomit.com",
    "-AdminPassword", "ChangeMeAfterLogin123!",
    "-AdminName", "Aims Admin",
    "-DbName", "aims_libcontrol",
    "-DbUser", "aims_libcontrol",
    "-DbPassword", "CHANGE_ME_ON_HOSTINGER",
    "-MailFrom", "admin@aims.phenomit.com"
)

if (-not $WithZip) {
    $invokeArgs += "-SkipZip"
}

if (-not $WithSql) {
    $invokeArgs += "-SkipSql"
}

powershell -ExecutionPolicy Bypass -File $clientScript @invokeArgs
