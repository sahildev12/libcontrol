<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $site['seo']['title'] ?? $site['library_name'] }}</title>
    <meta name="description" content="{{ $site['seo']['description'] ?? '' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/library-website.css', 'resources/js/library-website.js'])
    <style>
        body.library-website { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .lw-display { font-family: 'Playfair Display', Georgia, serif; }
    </style>
</head>
<body class="library-website">
    @yield('content')

    <div data-lw-lightbox class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/90 p-4">
        <button type="button" data-lw-lightbox-close class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Close gallery">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img data-lw-lightbox-image src="" alt="" class="max-h-[85vh] max-w-5xl rounded-2xl object-contain shadow-2xl">
    </div>
</body>
</html>
