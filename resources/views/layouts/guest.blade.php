<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?: $name }}</title>

        @if ($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @else
            <link rel="icon" href="{{ asset('logo/png-background/bg-blue-lc-logo.png') }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-gradient-to-br from-brand-blue via-brand-navy to-brand-navy px-4 py-10 sm:flex sm:items-center sm:justify-center sm:py-16">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 text-center">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="mx-auto h-16 max-w-[220px] object-contain">
                    @else
                        <img src="{{ asset('logo/png-background/lc-logo-landscape.png') }}" alt="LibControl" class="mx-auto h-14 max-w-[240px] object-contain">
                    @endif
                    <h1 class="mt-5 text-xl font-bold text-white">{{ $name }}</h1>
                    @if ($title)
                        <p class="mt-1 text-sm font-semibold text-brand-yellow">{{ $title }}</p>
                    @endif
                    @if ($subtitle)
                        <p class="mt-0.5 text-xs text-white/70">{{ $subtitle }}</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-white/10 bg-white px-6 py-6 shadow-2xl shadow-brand-navy/30">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-white/60">
                    {{ config('libcontrol.product.byline') }}
                </p>
            </div>
        </div>
    </body>
</html>
