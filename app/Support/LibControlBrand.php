<?php

namespace App\Support;

class LibControlBrand
{
    /** Logo for dark backgrounds (sidebar, login hero). */
    public static function darkIconUrl(): string
    {
        return asset(config('libcontrol.brand.dark_icon', 'logo/png-background/yellow-lc-logo.png'));
    }

    public static function darkWideUrl(): string
    {
        return asset(config('libcontrol.brand.dark_wide', 'logo/png-background/yellow-lc-logo.png'));
    }

    /** White logo with text (single image) for dark backgrounds — login, hero, etc. */
    public static function darkWideLogoWithTextUrl(): string
    {
        $path = config('libcontrol.brand.dark_wide_logo_with_text', 'logo/png-background/white-lc-logo-landscape.png');

        return asset($path);
    }

    /** Logo for light backgrounds (white cards, documents). */
    public static function lightIconUrl(): string
    {
        return asset(config('libcontrol.brand.light_icon', 'logo/png-background/yellow-lc-logo.png'));
    }

    public static function lightWideUrl(): string
    {
        return asset(config('libcontrol.brand.light_wide', 'logo/png-background/lc-logo-landscape.png'));
    }

    /** Blue favicon for light browser chrome / tabs. */
    public static function faviconLightUrl(): string
    {
        return asset(config('libcontrol.brand.default_favicon_light', 'logo/png-background/light-favicon/favicon-32x32.png'));
    }

    public static function faviconLight16Url(): string
    {
        return asset(config('libcontrol.brand.default_favicon_light_16', 'logo/png-background/light-favicon/favicon-16x16.png'));
    }

    public static function faviconLightIcoUrl(): string
    {
        return asset(config('libcontrol.brand.default_favicon_light_ico', 'logo/png-background/light-favicon/favicon.ico'));
    }

    public static function faviconLightAppleUrl(): string
    {
        return asset(config('libcontrol.brand.default_favicon_light_apple', 'logo/png-background/light-favicon/apple-touch-icon.png'));
    }

    /** White favicon for dark browser chrome / tabs. */
    public static function faviconDarkUrl(): string
    {
        return asset(config('libcontrol.brand.default_favicon_dark', 'logo/png-background/dark-favicon/favicon-32x32.png'));
    }

    public static function faviconDark16Url(): string
    {
        return asset(config('libcontrol.brand.default_favicon_dark_16', 'logo/png-background/dark-favicon/favicon-16x16.png'));
    }

    public static function faviconDarkIcoUrl(): string
    {
        return asset(config('libcontrol.brand.default_favicon_dark_ico', 'logo/png-background/dark-favicon/favicon.ico'));
    }

    public static function faviconDarkAppleUrl(): string
    {
        return asset(config('libcontrol.brand.default_favicon_dark_apple', 'logo/png-background/dark-favicon/apple-touch-icon.png'));
    }

    /**
     * @return array{light: string, dark: string}
     */
    public static function faviconAdaptiveUrls(): array
    {
        return [
            'light' => self::faviconLightUrl(),
            'dark' => self::faviconDarkUrl(),
        ];
    }
}
