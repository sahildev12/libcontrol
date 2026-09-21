@echo off
setlocal

rem Run from the LibControl project root (parent of scripts\).
cd /d "%~dp0.."

php artisan libcontrol:create-client-admin admin@gmail.com 123456789 --name="Library Admin"
if errorlevel 1 (
    echo.
    echo Failed to create client admin.
    exit /b 1
)

echo.
echo Done. Sign in at /admin/login with admin@gmail.com
endlocal
