<?php

declare(strict_types=1);

use App\Services\Payments\PhonePeClient;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = app(PhonePeClient::class);

echo 'PhonePe environment: '.$client->environment().PHP_EOL;
echo 'Configured: '.($client->configured() ? 'yes' : 'no').PHP_EOL;

if (! $client->configured()) {
    fwrite(STDERR, "Missing PHONEPE_CLIENT_ID / PHONEPE_CLIENT_SECRET.\n");
    exit(1);
}

$client->forgetToken();

$merchantOrderId = 'lc_ping_'.bin2hex(random_bytes(8));

try {
    $order = $client->createOrder(
        $merchantOrderId,
        100,
        'https://libcontrol.in/',
        'LibControl gateway connectivity test',
    );
    $redirect = (string) ($order['redirect_url'] ?? '');
    $host = parse_url($redirect, PHP_URL_HOST) ?: '';

    echo "Create order: OK\n";
    echo "Redirect host: {$host}\n";
    echo "Redirect URL: {$redirect}\n";

    if ($client->environment() === 'production' && str_contains($host, 'uat')) {
        fwrite(STDERR, "WARNING: production env but UAT redirect host.\n");
        exit(1);
    }

    if ($client->environment() === 'sandbox' && ! str_contains($host, 'uat') && ! str_contains($host, 'preprod')) {
        fwrite(STDERR, "WARNING: sandbox env but non-UAT redirect host.\n");
        exit(1);
    }

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Create order failed: '.$e->getMessage().PHP_EOL);
    exit(1);
}
