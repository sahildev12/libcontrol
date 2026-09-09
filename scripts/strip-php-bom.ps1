# Remove UTF-8 BOM from PHP files (fixes invisible output breaking HTML/JSON).
# Usage:
#   powershell -ExecutionPolicy Bypass -File scripts/strip-php-bom.ps1 -Path C:\path\to\libcontrol

param(
    [Parameter(Mandatory = $true)][string]$Path
)

$ErrorActionPreference = "Stop"
$root = Resolve-Path $Path

$fixed = 0
Get-ChildItem -Path $root -Recurse -Include *.php -File |
    Where-Object { $_.FullName -notmatch '\\vendor\\' } |
    ForEach-Object {
        $bytes = [System.IO.File]::ReadAllBytes($_.FullName)
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $text = [System.Text.Encoding]::UTF8.GetString($bytes, 3, $bytes.Length - 3)
            $encoding = New-Object System.Text.UTF8Encoding $false
            [System.IO.File]::WriteAllText($_.FullName, $text, $encoding)
            Write-Host "Stripped BOM: $($_.FullName.Substring($root.Path.Length + 1))"
            $fixed++
        }
    }

if ($fixed -eq 0) {
    Write-Host "No UTF-8 BOM found in PHP files under $root"
} else {
    Write-Host "Done. Fixed $fixed file(s)."
}
