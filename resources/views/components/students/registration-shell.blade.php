@props([
    'branchName' => null,
    'heading' => null,
    'description' => null,
    'pageTitle' => 'Student Registration',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}@if ($branchName) — {{ $branchName }}@endif</title>
        <link rel="icon" href="{{ asset('logo/png-background/yellow-lc-logo.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-white font-sans text-gray-900 antialiased">
        <div class="mx-auto min-h-screen max-w-2xl px-4 py-8 sm:px-6 sm:py-10">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                @if ($heading)
                    <div class="border-b border-gray-200 px-6 py-5">
                        <div class="flex items-start gap-3">
                            <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-blue/10 text-brand-blue">
                                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h1 class="text-xl font-bold text-brand-navy">{{ $heading }}</h1>
                                @if ($description)
                                    <p class="mt-1 text-sm leading-relaxed text-gray-600">{{ $description }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </div>

            <p class="mt-6 text-center text-xs text-gray-500">
                {{ config('libcontrol.product.byline') }}
            </p>
        </div>
    </body>
</html>
