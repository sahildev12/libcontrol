<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$env = config('payments.phonepe.env');
$authUrl = config('payments.phonepe.endpoints.'.$env.'.auth');

$response = Illuminate\Support\Facades\Http::asForm()
    ->acceptJson()
    ->post((string) $authUrl, [
        'client_id' => (string) config('payments.phonepe.client_id'),
        'client_version' => (string) config('payments.phonepe.client_version', '1'),
        'client_secret' => (string) config('payments.phonepe.client_secret'),
        'grant_type' => 'client_credentials',
    ]);

echo 'env='.$env.PHP_EOL;
echo 'auth_url='.$authUrl.PHP_EOL;
echo 'client_id='.config('payments.phonepe.client_id').PHP_EOL;
echo 'status='.$response->status().PHP_EOL;
echo 'body='.$response->body().PHP_EOL;
