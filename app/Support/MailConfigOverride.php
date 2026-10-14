<?php

namespace App\Support;

class MailConfigOverride
{
    public static function apply(): void
    {
        $path = storage_path('app/private/mail-settings.php');

        if (! is_readable($path)) {
            return;
        }

        $settings = require $path;

        if (! is_array($settings)) {
            return;
        }

        foreach ($settings as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'mail.')) {
                continue;
            }

            config([$key => $value]);
        }
    }
}
