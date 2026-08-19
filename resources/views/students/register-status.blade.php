<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-100 font-sans text-gray-900 antialiased">
        <div class="mx-auto flex min-h-screen max-w-lg items-center px-4 py-8">
            <div class="w-full rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto inline-flex size-14 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1 class="mt-4 text-xl font-bold text-gray-900">{{ $title }}</h1>
                <p class="mt-2 text-sm leading-6 text-gray-600">{{ $message }}</p>
            </div>
        </div>
    </body>
</html>
