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

    /** Logo for light backgrounds (white cards, documents). */
    public static function lightIconUrl(): string
    {
        return asset(config('libcontrol.brand.light_icon', 'logo/png-background/yellow-lc-logo.png'));
    }

    public static function lightWideUrl(): string
    {
        return asset(config('libcontrol.brand.light_wide', 'logo/png-background/yellow-lc-logo.png'));
    }
}
