<?php

namespace App\Support;

final class WhatsAppLink
{
    public static function url(string $phone, ?string $text = null): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        $url = 'https://wa.me/'.$digits;

        if ($text !== null && $text !== '') {
            $url .= '?text='.rawurlencode($text);
        }

        return $url;
    }
}
