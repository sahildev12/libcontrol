<?php

namespace App\Support\Tenancy;

class SocialLogin
{
    public const PROVIDERS = [
        'google' => 'Google',
        'facebook' => 'Facebook',
    ];

    /**
     * @return array<string, string> provider key => label, for providers with credentials configured
     */
    public static function providers(): array
    {
        if (! config('libcontrol.trials.enabled') || ! config('libcontrol.tenancy.enabled')) {
            return [];
        }

        return array_filter(
            self::PROVIDERS,
            fn (string $label, string $provider) => filled(config("services.{$provider}.client_id"))
                && filled(config("services.{$provider}.client_secret")),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public static function enabled(string $provider): bool
    {
        return array_key_exists($provider, self::providers());
    }

    public static function label(string $provider): string
    {
        return self::PROVIDERS[$provider] ?? ucfirst($provider);
    }

    /**
     * Absolute URL on the main site that starts the provider sign-in.
     */
    public static function redirectUrl(string $provider, ?string $library = null): string
    {
        $url = rtrim((string) config('app.url'), '/').'/auth/'.$provider.'/redirect';

        return $library !== null ? $url.'?library='.urlencode($library) : $url;
    }
}
