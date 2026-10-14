<?php

declare(strict_types=1);

/**
 * Build storage/app/private/mail-settings.php from a KEY=VALUE env file.
 * Usage: php scripts/build-mail-settings-php.php path/to/mail.env > mail-settings.php
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/build-mail-settings-php.php <mail.env>\n");
    exit(1);
}

$path = $argv[1];
if (! is_readable($path)) {
    fwrite(STDERR, "Cannot read: {$path}\n");
    exit(1);
}

$env = [];
foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    if (! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
        $value = substr($value, 1, -1);
    }
    $env[$key] = $value;
}

$map = [
    'MAIL_MAILER' => 'mail.default',
    'MAIL_SCHEME' => 'mail.mailers.smtp.scheme',
    'MAIL_HOST' => 'mail.mailers.smtp.host',
    'MAIL_PORT' => 'mail.mailers.smtp.port',
    'MAIL_USERNAME' => 'mail.mailers.smtp.username',
    'MAIL_PASSWORD' => 'mail.mailers.smtp.password',
    'MAIL_FROM_ADDRESS' => 'mail.from.address',
    'MAIL_FROM_NAME' => 'mail.from.name',
];

$config = [];
foreach ($map as $envKey => $configKey) {
    if (isset($env[$envKey]) && $env[$envKey] !== '') {
        $config[$configKey] = $envKey === 'MAIL_PORT' ? (int) $env[$envKey] : $env[$envKey];
    }
}

if ($config === []) {
    fwrite(STDERR, "No MAIL_* keys found in {$path}\n");
    exit(1);
}

echo "<?php\n\nreturn ".var_export($config, true).";\n";
