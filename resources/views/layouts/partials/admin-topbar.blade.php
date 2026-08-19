@php
    $navItems = config('admin-nav.primary', []);
    $currentRoute = request()->route()?->getName();
@endphp

<header class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 md:px-6">
    <div class="flex min-w-0 items-center gap-2 sm:gap-3 lg:hidden">
        <button
            type="button"
            x-data
            @click="$dispatch('toggle-mobile-nav')"
            class="inline-flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 hover:bg-white"
            aria-label="Open navigation"
        >
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-gray-900">{{ $activeBranch?->display_name ?? $activeBranch?->name ?? config('app.name') }}</p>
            @if ($isPlatformAdmin)
                <p class="truncate text-xs text-indigo-600">{{ $adminTypeLabel }}</p>
            @endif
        </div>
    </div>

    <div class="hidden min-w-0 lg:block" x-data="branchSwitcher({ switchUrl: @js(route('active-branch.switch')) })" @if ($isPlatformAdmin) x-init="init()" @endif>
        @if ($isPlatformAdmin)
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $adminTypeLabel }}</p>
            <div class="mt-0.5 flex items-center gap-2">
                <label class="shrink-0 text-xs text-gray-500">Active branch</label>
                <x-admin.select
                    wrapper-class="relative inline-block"
                    class="py-1.5 pl-3 pr-9 text-gray-800"
                    x-model="branchId"
                    @change="switchBranch()"
                >
                    @foreach ($allBranches as $branch)
                        <option value="{{ $branch->id }}" @selected($activeBranch?->id === $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </x-admin.select>
            </div>
        @else
            <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Branch</p>
            <p class="truncate text-sm font-medium text-gray-900">{{ $activeBranch?->name ?? 'Unassigned' }}</p>
        @endif
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                @click="open = !open"
                class="relative inline-flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-white"
                aria-label="Notifications"
            >
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if ($alertCount > 0)
                    <span class="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $alertCount > 9 ? '9+' : $alertCount }}</span>
                @endif
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition
                class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl sm:w-96"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Recent Notifications</h3>
                    <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                </div>
                <div class="max-h-80 divide-y divide-gray-100 overflow-y-auto">
                    @forelse ($recentAlerts as $alert)
                        <div class="px-4 py-3">
                            <div class="flex items-start gap-2">
                                <span @class([
                                    'mt-1 size-2 shrink-0 rounded-full',
                                    'bg-amber-500' => $alert['type'] === 'fee_expiring',
                                    'bg-red-500' => $alert['type'] === 'fee_expired',
                                    'bg-indigo-500' => $alert['type'] === 'new_enquiry',
                                ])></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ $alert['title'] }}</p>
                                    <p class="mt-0.5 text-xs text-gray-600">{{ $alert['message'] }}</p>
                                    <p class="mt-1 text-[11px] text-gray-400">{{ $alert['date'] }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-gray-500">No recent notifications.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button type="button" class="inline-flex size-9 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white hover:bg-indigo-700" aria-label="Account menu">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                    @if ($adminTypeLabel)
                        <p class="text-xs font-medium text-indigo-600">{{ $adminTypeLabel }}</p>
                    @endif
                </div>
                <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>

<nav
    x-data="{ open: false }"
    x-on:toggle-mobile-nav.window="open = !open"
    x-show="open"
    x-cloak
    class="flex shrink-0 gap-1 overflow-x-auto border-b border-gray-200 bg-white px-4 py-2 lg:hidden"
>
    @foreach ($navItems as $item)
        @php
            if (($item['platform_admin_only'] ?? false) && ! ($isPlatformAdmin ?? false)) {
                continue;
            }
            $disabled = ($item['disabled'] ?? false) || empty($item['route']);
            $isActive = ! $disabled && $item['route'] && ($currentRoute === $item['route'] || str_starts_with((string) $currentRoute, strtok($item['route'], '.').'.'));
        @endphp
        @if ($disabled)
            <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium text-gray-400">{{ $item['label'] }}</span>
        @else
            <a
                href="{{ route($item['route']) }}"
                class="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium {{ $isActive ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}"
            >
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>
