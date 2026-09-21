<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'LIBCONTROL_DEVELOPER_EMAIL='.(config('libcontrol.install.developer_email') ?: '(not set)').PHP_EOL;
echo 'LIBCONTROL_DEVELOPER_PASSWORD='.(config('libcontrol.install.developer_password') ?: '(not set)').PHP_EOL;
echo 'LIBCONTROL_ADMIN_EMAIL='.(config('libcontrol.install.admin_email') ?: '(not set)').PHP_EOL;
echo 'LIBCONTROL_ADMIN_PASSWORD='.(config('libcontrol.install.admin_password') ?: '(not set)').PHP_EOL;
echo PHP_EOL.'--- users ---'.PHP_EOL;

foreach (App\Models\User::query()->with('adminProfile')->orderBy('email')->get() as $user) {
    $role = $user->branch_id ? 'branch_staff' : ($user->adminProfile?->admin_type ?? 'user');
    echo $user->email.' | '.$user->name.' | '.$role.PHP_EOL;
}
