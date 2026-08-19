<?php

namespace App\Support;

class FormValidators
{
    public static function indianPhone(?string $value, bool $required = false): ?string
    {
        $cleaned = trim((string) $value);

        if ($cleaned === '') {
            return $required ? 'Phone number is required.' : null;
        }

        if (! preg_match('/^[6-9]\d{9}$/', $cleaned)) {
            return 'Enter a valid 10-digit Indian mobile number.';
        }

        return null;
    }

    public static function email(?string $value, bool $required = false): ?string
    {
        $cleaned = trim((string) $value);

        if ($cleaned === '') {
            return $required ? 'Email is required.' : null;
        }

        if (! filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }

        return null;
    }
}
