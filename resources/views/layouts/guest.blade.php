<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?: $name }} · {{ config('libspace.product.name') }}</title>

        @if ($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-gradient-to-b from-indigo-50 via-gray-50 to-gray-100 px-4 py-10 sm:flex sm:items-center sm:justify-center sm:py-16">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-6 text-center">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="mx-auto h-16 max-w-[220px] object-contain">
                    @else
                        <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white shadow-lg">
                            {{ strtoupper(substr($name, 0, 1)) }}
                        </div>
                    @endif
                    <h1 class="mt-4 text-xl font-bold text-gray-900">{{ $name }}</h1>
                    @if ($title)
                        <p class="mt-1 text-sm font-semibold text-indigo-700">{{ $title }}</p>
                    @endif
                    @if ($subtitle)
                        <p class="mt-0.5 text-xs text-gray-500">{{ $subtitle }}</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-xl">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-gray-500">
                    {{ config('libspace.product.byline') }}
                </p>
            </div>
        </div>
    </body>
</html>
