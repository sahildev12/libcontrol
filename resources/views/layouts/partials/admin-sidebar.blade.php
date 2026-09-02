@php
    $navItems = config('admin-nav.primary', []);
    $currentRoute = request()->route()?->getName();
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 hidden lg:flex flex-col overflow-hidden border-r border-gray-200 bg-white transition-[width] duration-200 ease-out"
    :class="collapsed ? 'w-[72px]' : 'w-[200px]'"
    aria-label="Admin navigation"
>
    <div
        class="flex h-14 shrink-0 items-center border-b border-gray-200"
        :class="collapsed ? 'justify-center px-2' : 'justify-between gap-2 px-2.5'"
    >
        <a
            href="{{ route('dashboard') }}"
            class="flex min-w-0 items-center gap-2.5"
            :class="collapsed ? 'justify-center' : ''"
            title="Dashboard"
        >
            @if (! empty($branding['simple_logo_url']))
                <img src="{{ $branding['simple_logo_url'] }}" alt="" class="size-7 shrink-0 rounded-md object-contain">
            @else
                <x-application-logo class="size-7 shrink-0 fill-current text-indigo-600" />
            @endif
            <div x-show="!collapsed" x-cloak class="min-w-0 leading-tight">
                <p class="truncate text-sm font-bold text-gray-900">{{ $branding['display_name'] ?? config('app.name') }}</p>
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">ADMIN PANEL</p>
            </div>
        </a>

        <button
            x-show="!collapsed"
            x-cloak
            type="button"
            @click="toggleCollapsed()"
            class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800"
            aria-label="Collapse sidebar"
        >
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
    </div>

    <div x-show="collapsed" x-cloak class="flex shrink-0 justify-center border-b border-gray-200 py-2">
        <button
            type="button"
            @click="toggleCollapsed()"
            class="inline-flex size-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800"
            aria-label="Expand sidebar"
        >
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-2 py-3" aria-label="Admin menu">
        <ul class="space-y-0.5">
            @foreach ($navItems as $item)
                @php
                    if (($item['platform_admin_only'] ?? false) && ! ($isPlatformAdmin ?? false)) {
                        continue;
                    }
                    if (($item['developer_admin_only'] ?? false) && ! ($isDeveloperAdmin ?? false)) {
                        continue;
                    }
                    if (($item['license_server_only'] ?? false) && ! ($licenseServerEnabled ?? false)) {
                        continue;
                    }
                    if (($item['tenancy_only'] ?? false) && ! ($tenancyEnabled ?? false)) {
                        continue;
                    }
                    $isActive = $item['route'] && ($currentRoute === $item['route'] || str_starts_with((string) $currentRoute, strtok($item['route'], '.').'.'));
                    $disabled = ($item['disabled'] ?? false) || empty($item['route']);
                @endphp
                <li>
                    @if ($disabled)
                        <span
                            class="flex items-center rounded-lg py-2 text-[13px] font-medium text-gray-400 cursor-not-allowed"
                            :class="collapsed ? 'justify-center px-2' : 'gap-2 px-2.5'"
                            title="Coming soon"
                        >
                            @include('layouts.partials.admin-nav-icon', ['icon' => $item['icon']])
                            <span x-show="!collapsed" x-cloak class="flex-1">{{ $item['label'] }}</span>
                        </span>
                    @else
                        <a
                            href="{{ route($item['route']) }}"
                            title="{{ $item['label'] }}"
                            class="flex items-center rounded-lg py-2 text-[13px] font-medium transition-colors {{ $isActive ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-indigo-50 hover:text-indigo-700' }}"
                            :class="collapsed ? 'justify-center px-2' : 'gap-2 px-2.5'"
                        >
                            @include('layouts.partials.admin-nav-icon', ['icon' => $item['icon'], 'active' => $isActive])
                            <span x-show="!collapsed" x-cloak class="flex-1">{{ $item['label'] }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="shrink-0 border-t border-gray-200 p-2">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center rounded-lg py-2 text-[13px] font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
                :class="collapsed ? 'justify-center px-2' : 'gap-2 px-2.5'"
                title="Log out"
            >
                <svg class="size-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/></svg>
                <span x-show="!collapsed" x-cloak>Log out</span>
            </button>
        </form>
    </div>
</aside>
