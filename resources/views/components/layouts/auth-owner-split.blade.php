@props([
    'title' => 'Admin log in',
    'activeTab' => 'login', // login|signup
])
@php
    use App\Support\LibControlBrand;
    $sidebarArt = asset('images/auth/owner-login-sidebar.jpg').'?v=6';
    $loginUrl = route('admin.login');
    $signupUrl = route('trials.create');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} · {{ config('libcontrol.product.name') }}</title>
        <x-favicon-links />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            html, body {
                height: 100%;
                margin: 0;
                overflow: hidden;
            }
            .lc-owner-shell {
                box-sizing: border-box;
                height: 100vh;
                height: 100dvh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0.65rem;
                overflow: hidden;
                background: linear-gradient(145deg, #0b1f5c 0%, #1d4ed8 48%, #2563eb 100%);
                font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            }
            .lc-owner-card {
                width: 100%;
                max-width: 980px;
                max-height: calc(100vh - 1.3rem);
                max-height: calc(100dvh - 1.3rem);
                display: grid;
                grid-template-columns: 1fr;
                overflow: hidden;
                border-radius: 1.5rem;
                background: #fff;
                box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
            }
            @media (min-width: 900px) {
                .lc-owner-card {
                    grid-template-columns: 1fr 1fr;
                    height: min(640px, calc(100vh - 1.3rem));
                    height: min(640px, calc(100dvh - 1.3rem));
                }
            }
            .lc-owner-panel {
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                padding: 1.15rem 1.25rem 1rem;
                min-width: 0;
                min-height: 0;
                overflow: hidden;
            }
            @media (min-width: 640px) {
                .lc-owner-panel { padding: 1.35rem 1.75rem 1.15rem; }
            }
            .lc-owner-body {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
                justify-content: center;
                min-height: 0;
            }
            .lc-owner-hello {
                margin: 0 0 0.25rem;
                font-size: 1.35rem;
                font-weight: 800;
                letter-spacing: -0.02em;
                line-height: 1.2;
                color: #0f172a;
            }
            .lc-owner-hello-sub {
                margin: 0 0 0.85rem;
                font-size: 0.8125rem;
                line-height: 1.4;
                color: #64748b;
            }
            .lc-owner-lead {
                margin: 0 0 0.85rem;
                font-size: 0.8125rem;
                line-height: 1.4;
                text-align: center;
                color: #64748b;
            }
            @media (min-width: 900px) {
                .lc-owner-hello {
                    margin-top: 0.75rem;
                }
                .lc-owner-lead {
                    margin-top: 0.75rem;
                }
            }
            .lc-owner-address {
                display: flex;
                overflow: hidden;
                border-radius: 0.85rem;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
            }
            .lc-owner-address:focus-within {
                border-color: #2563eb;
                background: #fff;
                box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
            }
            .lc-owner-address__input {
                display: block;
                width: 100%;
                min-width: 0;
                flex: 1;
                border: 0;
                background: transparent;
                padding: 0.7rem 2.25rem 0.7rem 0.9rem;
                font-size: 0.875rem;
                color: #0f172a;
                text-transform: lowercase;
            }
            .lc-owner-address__input:focus {
                outline: none;
                box-shadow: none;
            }
            .lc-owner-address__suffix {
                display: inline-flex;
                flex-shrink: 0;
                align-items: center;
                border-left: 1px solid #e2e8f0;
                background: #f1f5f9;
                padding: 0 0.75rem;
                font-size: 0.75rem;
                font-weight: 500;
                color: #64748b;
            }
            .lc-owner-social-block {
                margin-top: 1.15rem;
            }
            .lc-owner-social-divider {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 0.85rem;
                font-size: 0.625rem;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #94a3b8;
            }
            .lc-owner-art {
                display: none;
                position: relative;
                min-width: 0;
                overflow: hidden;
                background: #1e40af;
            }
            @media (min-width: 900px) {
                .lc-owner-art { display: block; min-height: 100%; }
            }
            .lc-owner-art__bg {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center 55%;
            }
            .lc-owner-art__shade {
                position: absolute;
                inset: 0;
                z-index: 1;
                pointer-events: none;
                background:
                    linear-gradient(180deg, rgba(15, 40, 110, 0.78) 0%, rgba(15, 40, 110, 0.35) 38%, rgba(15, 40, 110, 0.08) 58%, rgba(15, 40, 110, 0.45) 100%),
                    linear-gradient(90deg, rgba(15, 40, 110, 0.28) 0%, transparent 45%);
            }
            .lc-owner-art__copy {
                position: relative;
                z-index: 2;
                padding: 1.35rem 1.35rem 0.75rem;
            }
            .lc-owner-art__title {
                margin: 0;
                font-size: clamp(1.35rem, 2vw, 1.7rem);
                font-weight: 800;
                line-height: 1.15;
                letter-spacing: -0.02em;
                color: #fff;
                text-shadow: 0 2px 14px rgba(15, 23, 42, 0.35);
            }
            .lc-owner-art__title span { color: #93c5fd; }
            .lc-owner-art__sub {
                margin: 0.4rem 0 0;
                font-size: 0.875rem;
                font-weight: 500;
                color: rgba(255, 255, 255, 0.92);
            }
            .lc-owner-art__features {
                list-style: none;
                margin: 0.85rem 0 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 0.4rem;
            }
            .lc-owner-art__features li {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                width: fit-content;
                max-width: 100%;
                padding: 0.35rem 0.75rem 0.35rem 0.45rem;
                border-radius: 999px;
                background: rgba(15, 23, 42, 0.38);
                color: #fff;
                font-size: 0.72rem;
                font-weight: 600;
                line-height: 1.2;
                backdrop-filter: blur(6px);
            }
            .lc-owner-art__features svg {
                width: 1.05rem;
                height: 1.05rem;
                flex-shrink: 0;
                color: #93c5fd;
            }
            .lc-owner-art__motto {
                position: absolute;
                z-index: 2;
                right: 1.25rem;
                bottom: 1.15rem;
                margin: 0;
                max-width: calc(100% - 2.5rem);
                text-align: right;
                font-family: Georgia, 'Times New Roman', serif;
                font-style: italic;
                font-size: 0.95rem;
                line-height: 1.35;
                color: rgba(255, 255, 255, 0.95);
                text-shadow: 0 2px 10px rgba(15, 23, 42, 0.45);
            }
            .lc-owner-tabs {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.3rem;
                margin-bottom: 0.85rem;
                padding: 0.25rem;
                border-radius: 999px;
                background: #eef2ff;
            }
            .lc-owner-tab {
                display: inline-flex;
                min-height: 2.25rem;
                align-items: center;
                justify-content: center;
                gap: 0.4rem;
                border-radius: 999px;
                padding: 0.35rem 0.75rem;
                font-size: 0.8125rem;
                font-weight: 700;
                color: #64748b;
                text-decoration: none;
                transition: background .15s, color .15s, box-shadow .15s;
            }
            .lc-owner-tab.is-active {
                background: #2563eb;
                color: #fff;
                box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
            }
            .lc-owner-form {
                display: flex;
                flex-direction: column;
                gap: 0.85rem;
            }
            .lc-owner-field-block {
                display: flex;
                flex-direction: column;
                gap: 0;
            }
            .lc-owner-label-row {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 0.75rem;
                margin: 0 0 0.45rem;
            }
            .lc-owner-label {
                display: block;
                margin: 0 0 0.45rem;
                font-size: 0.8125rem;
                font-weight: 600;
                line-height: 1.25;
                color: #334155;
            }
            .lc-owner-label-row .lc-owner-label { margin: 0; }
            .lc-owner-field { position: relative; }
            .lc-owner-field__icon {
                pointer-events: none;
                position: absolute;
                left: 0.85rem;
                top: 50%;
                z-index: 1;
                width: 1rem;
                height: 1rem;
                transform: translateY(-50%);
                color: #94a3b8;
            }
            .lc-owner-input {
                width: 100%;
                border-radius: 0.85rem;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                padding: 0.7rem 0.9rem 0.7rem 2.45rem;
                font-size: 0.875rem;
                color: #0f172a;
                transition: border-color .15s, box-shadow .15s, background .15s;
            }
            .lc-owner-input:focus {
                outline: none;
                border-color: #2563eb;
                background: #fff;
                box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
            }
            .lc-owner-btn {
                display: inline-flex;
                width: 100%;
                min-height: 2.55rem;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                border: 0;
                border-radius: 10px;
                padding: 0.65rem 1.15rem;
                background: linear-gradient(#4f9cf9, #2563eb);
                color: #fff;
                font-size: 0.8125rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                cursor: pointer;
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.28),
                    inset 0 -2px 0 rgba(0, 0, 0, 0.18),
                    0 1px 2px rgba(0, 0, 0, 0.28);
                transition: background .2s ease, box-shadow .15s ease, translate .08s ease;
            }
            .lc-owner-btn:hover {
                background: linear-gradient(#6aafff, #3b82f6);
                color: #fff;
            }
            .lc-owner-btn:active {
                translate: 0 1px;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25);
            }
            .lc-owner-btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }
            .lc-owner-social {
                display: inline-flex;
                height: 2.35rem;
                width: 2.35rem;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #fff;
                transition: border-color .15s, box-shadow .15s;
            }
            .lc-owner-foot {
                margin-top: 0.75rem;
                text-align: center;
                font-size: 0.75rem;
                color: #64748b;
            }
            .lc-owner-byline {
                margin-top: 0.45rem;
                text-align: center;
                font-size: 0.6875rem;
                color: #94a3b8;
            }
            .lc-owner-social:hover {
                border-color: #cbd5e1;
                box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
            }
        </style>
    </head>
    <body class="antialiased text-slate-900">
        <div class="lc-owner-shell">
            <div class="lc-owner-card">
                <div class="lc-owner-panel">
                    <a href="{{ url('/') }}" class="mb-3 inline-flex self-start no-underline">
                        <img src="{{ LibControlBrand::lightWideUrl() }}" alt="LibControl" class="h-8 w-auto object-contain">
                    </a>

                    <div class="lc-owner-tabs" role="tablist" aria-label="Account">
                        <a href="{{ $loginUrl }}" role="tab" aria-selected="{{ $activeTab === 'login' ? 'true' : 'false' }}" class="lc-owner-tab {{ $activeTab === 'login' ? 'is-active' : '' }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Login
                        </a>
                        <a href="{{ $signupUrl }}" role="tab" aria-selected="{{ $activeTab === 'signup' ? 'true' : 'false' }}" class="lc-owner-tab {{ $activeTab === 'signup' ? 'is-active' : '' }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Sign Up
                        </a>
                    </div>

                    <div class="lc-owner-body">
                        {{ $slot }}
                    </div>
                </div>
                <aside class="lc-owner-art" aria-hidden="true">
                    <img class="lc-owner-art__bg" src="{{ $sidebarArt }}" alt="" width="864" height="1152">
                    <div class="lc-owner-art__shade"></div>
                    <div class="lc-owner-art__copy">
                        <p class="lc-owner-art__title">Smart Library <span>Growth</span> System</p>
                        <p class="lc-owner-art__sub">Manage. Monitor. Grow.</p>
                        <ul class="lc-owner-art__features">
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h4V9H4v10zm6 0h4V5h-4v14zm6 0h4v-7h-4v7z"/></svg>
                                Manage Seats &amp; Students
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 7-7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 8h6v6"/></svg>
                                Track Growth with Analytics
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                Handle Fees &amp; Subscriptions
                            </li>
                        </ul>
                    </div>
                    <p class="lc-owner-art__motto">Empowering Libraries, Building Learning</p>
                </aside>
            </div>
        </div>
    </body>
</html>
