<?php

declare(strict_types=1);

use App\Services\MailDeliveryService;
use Illuminate\Support\Facades\Mail;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$delivery = app(MailDeliveryService::class);
$issue = $delivery->issue();
if ($issue !== null) {
    fwrite(STDERR, "Mail not configured: {$issue}\n");
    exit(1);
}

$to = $argv[1] ?? (string) config('libcontrol.support.email', 'support@libcontrol.in');

try {
    Mail::raw('LibControl SMTP test at '.date('c'), function ($message) use ($to) {
        $message->to($to)->subject('LibControl SMTP test');
    });
    echo "Sent test message to {$to}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Send failed: '.$e->getMessage()."\n");
    exit(1);
}
