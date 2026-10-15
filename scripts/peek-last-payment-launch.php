<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$payment = App\Models\Payment::query()->orderByDesc('id')->first();
if (! $payment) {
    echo "no payments\n";
    exit(0);
}

$service = app(App\Services\Payments\PaymentService::class);
echo 'deployment_domain='.$payment->deployment_domain."\n";
echo 'phonepe_redirect='.substr((string) ($payment->meta['phonepe_redirect_url'] ?? ''), 0, 80)."\n";
echo 'launch_url='.$service->signedLaunchUrl($payment)."\n";
