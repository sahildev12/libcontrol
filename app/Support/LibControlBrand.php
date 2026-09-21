<?php

namespace App\Support;

class LibControlBrand
{
    /** Logo for dark backgrounds (sidebar, login hero). */
    public static function darkIconUrl(): string
    {
        return asset(config('libcontrol.brand.dark_icon', 'logo/bg-blue-lc-logo.jpg.jpeg'));
    }

    public static function darkWideUrl(): string
    {
        return asset(config('libcontrol.brand.dark_wide', 'logo/png-background/bg-blue-lc-logo.png'));
    }

    /** Logo for light backgrounds (white cards, documents). */
    public static function lightIconUrl(): string
    {
        return asset(config('libcontrol.brand.light_icon', 'logo/bg-white-lc-logo.jpg.jpeg'));
    }

    public static function lightWideUrl(): string
    {
        return asset(config('libcontrol.brand.light_wide', 'logo/png-background/lc-logo-landscape.png'));
    }
}
