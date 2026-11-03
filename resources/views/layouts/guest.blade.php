@php
    use App\Support\LibControlBrand;

    $usesDarkLoginLogo = in_array($portal, ['branch', 'admin', 'developer', 'auth'], true);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?: $name }}</title>

        <x-favicon-links :custom-url="$faviconUrl" :custom="$faviconCustom" />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-gradient-to-br from-brand-blue via-brand-navy to-brand-navy px-4 py-10 sm:flex sm:items-center sm:justify-center {{ $compact ? 'sm:py-6' : 'sm:py-16' }}">
            <div class="mx-auto w-full {{ $compact ? 'max-w-md sm:max-w-lg' : 'max-w-md' }}">
                <div class="{{ $compact ? 'mb-5' : ($usesDarkLoginLogo ? 'mb-10' : 'mb-8') }} text-center">
                    @if ($compact)
                        <img src="{{ LibControlBrand::darkWideLogoWithTextUrl() }}" alt="LibControl" class="mx-auto mb-3 h-14 w-full max-w-[240px] object-contain">
                    @elseif ($usesDarkLoginLogo)
                        <x-auth.login-logo-on-dark />
                    @elseif ($logoUrl)
                        <div class="mx-auto flex size-20 items-center justify-center rounded-2xl bg-white p-3 shadow-lg shadow-brand-navy/20">
                            <img src="{{ $logoUrl }}" alt="{{ $name }}" class="max-h-14 max-w-full object-contain">
                        </div>
                    @else
                        <img src="{{ LibControlBrand::darkWideUrl() }}" alt="LibControl" class="mx-auto h-16 max-w-[240px] object-contain">
                    @endif
                    @if (! $usesDarkLoginLogo)
                        <h1 class="mt-5 text-xl font-bold text-white">{{ $name }}</h1>
                    @endif
                    @if ($title)
                        <p class="{{ $usesDarkLoginLogo ? 'mt-0' : 'mt-1' }} text-sm font-semibold text-brand-yellow">{{ $title }}</p>
                    @endif
                    @if ($subtitle)
                        <p class="{{ $usesDarkLoginLogo ? 'mt-2' : 'mt-0.5' }} text-xs text-white/70">{{ $subtitle }}</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-white/10 bg-white {{ $compact ? 'px-6 py-5 sm:px-7' : 'px-6 py-6' }} shadow-2xl shadow-brand-navy/30">
                    {{ $slot }}
                </div>

                <p class="{{ $compact ? 'mt-4' : 'mt-6' }} text-center text-xs text-white/60">
                    {{ config('libcontrol.product.byline') }}
                </p>
            </div>
        </div>
    </body>
</html>
