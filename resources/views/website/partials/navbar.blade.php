<header
    data-lw-header
    class="fixed inset-x-0 top-0 z-50 border-b border-transparent bg-white/90 backdrop-blur-md transition [.is-scrolled_&]:border-slate-200 [.is-scrolled_&]:shadow-sm"
>
    <div class="lw-container flex items-center justify-between gap-4 py-3">
        <a href="#home" class="flex min-w-0 items-center gap-3">
            @if ($site['logo_url'])
                <img src="{{ $site['logo_url'] }}" alt="{{ $site['library_name'] }}" class="h-11 w-auto max-w-[140px] object-contain">
            @else
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-800 text-sm font-bold text-white">SH</div>
            @endif
            <div class="min-w-0">
                <p class="truncate text-sm font-bold uppercase tracking-wide text-slate-900">{{ $site['library_name'] }}</p>
                <p class="truncate text-xs text-slate-500">{{ $site['subtitle'] }}</p>
            </div>
        </a>

        <nav class="hidden items-center gap-6 lg:flex" aria-label="Main navigation">
            @foreach ($site['navigation'] as $item)
                <a href="{{ $item['href'] }}" data-lw-nav-link class="lw-nav-link {{ $item['href'] === '#home' ? 'is-active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            <a href="{{ $site['branch_login_url'] }}" class="text-sm font-medium text-slate-500 hover:text-blue-800">Staff login</a>
            <a href="#contact" class="lw-btn-primary">Enquire Now</a>
        </div>

        <button type="button" data-lw-menu-toggle class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-700 lg:hidden" aria-expanded="false" aria-label="Open menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <div data-lw-mobile-menu class="hidden border-t border-slate-200 bg-white lg:hidden">
        <nav class="lw-container flex flex-col gap-1 py-4" aria-label="Mobile navigation">
            @foreach ($site['navigation'] as $item)
                <a href="{{ $item['href'] }}" data-lw-nav-link class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ $site['branch_login_url'] }}" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 hover:bg-slate-50">Staff login</a>
            <a href="#contact" class="lw-btn-primary mt-2 w-full">Enquire Now</a>
        </nav>
    </div>
</header>
