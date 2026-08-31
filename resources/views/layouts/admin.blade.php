<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
            @php
                $metaBranchId = auth()->user()->branch_id;

                if (! $metaBranchId && auth()->user()->isPlatformAdmin()) {
                    try {
                        if (! app(\App\Services\BranchContext::class)->viewingAll(auth()->user(), request())) {
                            $metaBranchId = app(\App\Services\BranchContext::class)->optionalBranchId(auth()->user(), request());
                        }
                    } catch (\Throwable) {
                        $metaBranchId = null;
                    }
                }
            @endphp
            @if ($metaBranchId)
                <meta name="branch-id" content="{{ $metaBranchId }}">
            @endif
        @endauth

        <title>{{ $branding['display_name'] ?? config('app.name') }}</title>

        @if (! empty($branding['favicon_url']))
            <link rel="icon" href="{{ $branding['favicon_url'] }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased overflow-hidden">
        <div
            x-data="adminShell()"
            x-init="init()"
            class="h-svh overflow-hidden bg-gray-100"
        >
            @include('layouts.partials.admin-sidebar')

            <div
                class="admin-main-shell flex h-svh min-h-0 min-w-0 flex-col transition-[margin] duration-200 ease-out"
                :class="collapsed ? 'is-collapsed' : ''"
            >
                @include('layouts.partials.admin-topbar')

                <main class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
                    <div class="mx-auto max-w-none space-y-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @include('layouts.partials.admin-toasts')
    </body>
</html>
