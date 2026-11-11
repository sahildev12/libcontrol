@php
    use App\Support\LibControlBrand;

    // Branch: blue→yellow brand shell. Admin: light library shell. Hub auth + developer: dark brand shell.
    $branchShell = $portal === 'branch';
    $lightShell = $portal === 'admin';
    $usesBrandLogoOnShell = in_array($portal, ['admin', 'branch', 'developer', 'auth'], true);
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
        @if ($branchShell)
            <style>
                .lc-branch-shell {
                    background: linear-gradient(145deg, #0b1f5c 0%, #1d4ed8 42%, #00b9f0 100%);
                }
            </style>
        @endif
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div
            @class([
                'min-h-screen px-4 py-10 sm:flex sm:items-center sm:justify-center sm:py-3',
                'lc-branch-shell' => $branchShell,
                'bg-[radial-gradient(ellipse_at_top,_#EEF3FF_0%,_#F4F7FB_45%,_#E8EEF8_100%)]' => $lightShell,
                'bg-gradient-to-br from-brand-blue via-brand-navy to-brand-navy' => ! $lightShell && ! $branchShell,
            ])
        >
            <div class="mx-auto w-full {{ $compact ? 'max-w-md sm:max-w-lg' : 'max-w-md' }}">
                <div class="{{ $usesBrandLogoOnShell ? 'mb-8' : 'mb-6' }} text-center sm:mb-4">
                    @if ($lightShell)
                        <img
                            src="{{ LibControlBrand::lightWideUrl() }}"
                            alt="LibControl"
                            class="mx-auto mb-8 h-16 w-full max-w-[260px] object-contain sm:mb-2 sm:h-11 sm:max-w-[200px]"
                        >
                    @elseif ($branchShell || $usesBrandLogoOnShell)
                        <x-auth.login-logo-on-dark />
                    @elseif ($logoUrl)
                        <div class="mx-auto flex size-20 items-center justify-center rounded-2xl bg-white p-3 shadow-lg shadow-brand-navy/20 sm:size-16 sm:p-2.5">
                            <img src="{{ $logoUrl }}" alt="{{ $name }}" class="max-h-14 max-w-full object-contain sm:max-h-11">
                        </div>
                    @else
                        <img src="{{ LibControlBrand::darkWideUrl() }}" alt="LibControl" class="mx-auto h-16 max-w-[240px] object-contain sm:h-12">
                    @endif
                    @if (! $usesBrandLogoOnShell)
                        <h1 @class(['mt-5 text-xl font-bold sm:mt-3', 'text-brand-navy' => $lightShell, 'text-white' => ! $lightShell])>{{ $name }}</h1>
                    @endif
                    @if ($title)
                        <p @class([
                            'text-sm font-semibold',
                            $usesBrandLogoOnShell ? 'mt-0' : 'mt-1',
                            'text-brand-blue' => $lightShell,
                            'text-brand-yellow' => $branchShell || (! $lightShell && ! $branchShell),
                        ])>{{ $title }}</p>
                    @endif
                    @if ($subtitle)
                        <p @class([
                            'text-xs',
                            $usesBrandLogoOnShell ? 'mt-2 sm:mt-1' : 'mt-0.5',
                            'text-slate-500' => $lightShell,
                            'text-white/75' => ! $lightShell,
                        ])>{{ $subtitle }}</p>
                    @endif
                </div>

                <div
                    @class([
                        'rounded-2xl bg-white px-6 py-6 sm:px-7',
                        $compact ? 'sm:py-4' : 'sm:py-5',
                        'border border-slate-200/80 shadow-xl shadow-slate-300/40' => $lightShell,
                        'border border-white/15 shadow-2xl shadow-brand-navy/35' => ! $lightShell,
                    ])
                >
                    {{ $slot }}
                </div>

                <p @class(['mt-6 text-center text-xs sm:mt-3', 'text-slate-500' => $lightShell, 'text-white/70' => ! $lightShell])>
                    {{ config('libcontrol.product.byline') }}
                </p>
            </div>
        </div>
    </body>
</html>
