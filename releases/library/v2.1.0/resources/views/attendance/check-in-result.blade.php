<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — {{ $branchName }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 font-sans text-gray-900 antialiased">
        <div class="mx-auto flex min-h-screen max-w-md items-center px-4 py-8">
            <div class="w-full overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm">
                <div class="mx-auto mb-4 inline-flex size-14 items-center justify-center rounded-full {{ $success ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }}">
                    @if ($success)
                        <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                <h1 class="text-xl font-bold text-gray-900">{{ $title }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>
                <p class="mt-4 text-xs text-gray-400">{{ $branchName }}</p>
            </div>
        </div>
    </body>
</html>
