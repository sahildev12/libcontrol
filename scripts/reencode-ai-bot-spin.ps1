# Re-encode AI bot spin MP4 for dashboard banner (matte #0b3680, no clipping).
# Requires: npm install ffmpeg-static --no-save (or ffmpeg on PATH)
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path $PSScriptRoot -Parent
$ff = Join-Path $repoRoot 'node_modules\ffmpeg-static\ffmpeg.exe'
$src = Join-Path $repoRoot 'public\logo\growth\ai-assistant-bot-spin.source.mp4'
$out = Join-Path $repoRoot 'public\logo\growth\ai-assistant-bot-spin.mp4'
if (-not (Test-Path $ff)) { throw 'Run: npm install ffmpeg-static --no-save' }
& $ff -y -i $src -t 4.2 -filter_complex "[0:v]crop=1080:1080:(iw-1080)/2:0,format=rgba,colorkey=0x11437B:0.18:0.03,scale=230:230:force_original_aspect_ratio=decrease[fg];color=c=0x0B3680:s=440x440:r=24:d=4.2,format=rgba[bg];[bg][fg]overlay=(W-w)/2:(H-h)/2:format=auto,format=yuv420p[v]" -map "[v]" -an -c:v libx264 -crf 22 -preset medium -pix_fmt yuv420p -movflags +faststart $out
Write-Host "Wrote $out ($((Get-Item $out).Length) bytes). Bump ?v= on ai-recommendation-banner.blade.php"
