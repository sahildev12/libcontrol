@php
    use App\Support\LibControlBrand;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Admin log in' }} · {{ config('libcontrol.product.name') }}</title>
        <x-favicon-links />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .lc-owner-shell {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.25rem;
                background:
                    radial-gradient(ellipse 80% 60% at 20% 10%, rgba(36, 58, 139, 0.18), transparent 55%),
                    radial-gradient(ellipse 70% 50% at 90% 90%, rgba(254, 207, 37, 0.12), transparent 50%),
                    linear-gradient(160deg, #0c0048 0%, #1a2f78 45%, #243a8b 100%);
                font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            }
            .lc-owner-card {
                width: 100%;
                max-width: 920px;
                display: grid;
                grid-template-columns: 1fr;
                overflow: hidden;
                border-radius: 1.5rem;
                background: #fff;
                box-shadow: 0 25px 60px rgba(12, 0, 72, 0.35);
            }
            @media (min-width: 880px) {
                .lc-owner-card { grid-template-columns: 1.05fr 0.95fr; min-height: 560px; }
            }
            .lc-owner-panel { padding: 2rem 1.75rem; }
            @media (min-width: 640px) {
                .lc-owner-panel { padding: 2.5rem 2.75rem; }
            }
            .lc-owner-art {
                display: none;
                position: relative;
                background:
                    linear-gradient(165deg, rgba(12, 0, 72, 0.25), rgba(36, 58, 139, 0.55)),
                    radial-gradient(circle at 30% 20%, #fecf25 0%, transparent 35%),
                    radial-gradient(circle at 80% 30%, #5b7fd4 0%, transparent 40%),
                    linear-gradient(180deg, #1e3a8a 0%, #0c0048 100%);
                overflow: hidden;
            }
            @media (min-width: 880px) {
                .lc-owner-art { display: block; }
            }
            .lc-owner-art__glow {
                position: absolute;
                inset: auto -20% -30% -10%;
                height: 70%;
                background: radial-gradient(ellipse at center, rgba(254, 207, 37, 0.35), transparent 65%);
                filter: blur(8px);
            }
            .lc-owner-art__copy {
                position: absolute;
                left: 1.75rem;
                right: 1.75rem;
                bottom: 2rem;
                color: #fff;
            }
            .lc-owner-input {
                margin-top: 0.4rem;
                width: 100%;
                border-radius: 0.9rem;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                padding: 0.8rem 1rem;
                font-size: 0.95rem;
                color: #0f172a;
                transition: border-color .15s, box-shadow .15s, background .15s;
            }
            .lc-owner-input:focus {
                outline: none;
                border-color: #243a8b;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(36, 58, 139, 0.15);
            }
            .lc-owner-btn {
                display: inline-flex;
                width: 100%;
                align-items: center;
                justify-content: center;
                border-radius: 0.9rem;
                background: #243a8b;
                padding: 0.85rem 1rem;
                font-size: 0.95rem;
                font-weight: 700;
                color: #fff;
                transition: background .15s, transform .15s;
            }
            .lc-owner-btn:hover { background: #0c0048; }
            .lc-owner-social {
                display: inline-flex;
                height: 2.75rem;
                width: 2.75rem;
                align-items: center;
                justify-content: center;
                border-radius: 0.85rem;
                border: 1px solid #e2e8f0;
                background: #fff;
                transition: border-color .15s, box-shadow .15s;
            }
            .lc-owner-social:hover {
                border-color: #cbd5e1;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            }
        </style>
    </head>
    <body class="antialiased text-slate-900">
        <div class="lc-owner-shell">
            <div class="lc-owner-card">
                <div class="lc-owner-panel flex flex-col">
                    <a href="{{ url('/') }}" class="mb-6 inline-flex items-center gap-2 self-start">
                        <img src="{{ LibControlBrand::lightWideUrl() }}" alt="LibControl" class="h-9 w-auto object-contain">
                    </a>
                    {{ $slot }}
                </div>
                <aside class="lc-owner-art" aria-hidden="true">
                    <div class="lc-owner-art__glow"></div>
                    <svg class="absolute inset-0 h-full w-full opacity-40" viewBox="0 0 400 560" preserveAspectRatio="xMidYMid slice">
                        <path fill="#fecf25" opacity="0.25" d="M0 360 C80 300 140 420 220 360 C300 300 340 380 400 340 L400 560 L0 560 Z"/>
                        <path fill="#ffffff" opacity="0.12" d="M0 420 C100 380 160 460 250 410 C330 365 360 430 400 400 L400 560 L0 560 Z"/>
                        <circle cx="310" cy="120" r="48" fill="#fecf25" opacity="0.55"/>
                        <rect x="70" y="250" width="70" height="110" rx="8" fill="#fff" opacity="0.18"/>
                        <rect x="155" y="210" width="55" height="150" rx="8" fill="#fff" opacity="0.22"/>
                        <rect x="225" y="270" width="80" height="90" rx="8" fill="#fff" opacity="0.16"/>
                    </svg>
                    <div class="lc-owner-art__copy">
                        <p class="text-2xl font-extrabold leading-snug tracking-tight">Finally, all your library work in one place.</p>
                        <p class="mt-2 text-sm text-white/75">Seats, students, fees and growth — managed by you.</p>
                    </div>
                </aside>
            </div>
        </div>
    </body>
</html>
