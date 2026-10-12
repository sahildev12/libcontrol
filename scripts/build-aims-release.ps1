# Build the Aims Library client release package.
# Default: staging folder only (no zip, no SQL) — for updating an existing aims.katsindia.com install.
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
    "-AppUrl", "https://aims.katsindia.com",
    "-Version", "2.1.6",
    "-StudentCodePrefix", "AIMS",
    "-AdminEmail", "admin@aims.katsindia.com",
    "-AdminPassword", "123456789",
    "-AdminName", "Aims Admin",
    "-DbName", "aims_libcontrol",
    "-DbUser", "aims_libcontrol",
    "-DbPassword", "CHANGE_ME_ON_HOSTINGER",
    "-MailFrom", "admin@aims.katsindia.com"
)

if (-not $WithZip) {
    $invokeArgs += "-SkipZip"
}

if (-not $WithSql) {
    $invokeArgs += "-SkipSql"
}

powershell -ExecutionPolicy Bypass -File $clientScript @invokeArgs
