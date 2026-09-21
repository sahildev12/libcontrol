<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$installLock = __DIR__.'/../storage/app/install.lock';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (! is_file($installLock) && ! in_array($requestPath, ['/up', '/favicon.ico'], true)
    && ! str_starts_with($requestPath, '/setup')
    && ! str_starts_with($requestPath, '/install')
    && ! str_starts_with($requestPath, '/build/')) {
    header('Location: /setup', true, 302);
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
