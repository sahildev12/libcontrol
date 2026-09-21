@php
    use App\Support\AdminNav;
    use App\Services\Addons\AddonRegistry;

    $navItems = config('admin-nav.primary', []);
    $currentRoute = request()->route()?->getName();
    $addonRegistry = app(AddonRegistry::class);
    $lcTheme = ! ($isDeveloperAdmin ?? false);
@endphp

<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-4 border-b px-4 md:px-6 lg:relative {{ $lcTheme ? 'border-gray-200/80 bg-white/95 shadow-sm backdrop-blur' : 'border-gray-200 bg-white' }}">
    <div class="flex min-w-0 items-center gap-2 sm:gap-3 lg:hidden">
        <button
            type="button"
            @click="$dispatch('toggle-mobile-nav')"
            class="inline-flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 hover:bg-white"
            aria-label="Open navigation"
        >
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-gray-900">{{ $viewingAllBranches ?? false ? 'All branches' : ($activeBranch?->display_name ?? $activeBranch?->name ?? config('app.name')) }}</p>
            @if ($adminTypeLabel)
                <p class="truncate text-xs {{ $lcTheme ? 'text-brand-blue' : 'text-indigo-600' }}">{{ $adminTypeLabel }}</p>
            @endif
        </div>
    </div>

    <div class="hidden min-w-0 flex-1 lg:block" x-data="branchSwitcher({ switchUrl: @js(route('active-branch.switch')) })" @if ($isAnyAdmin ?? false) x-init="init()" @endif>
        @if ($isAnyAdmin ?? false)
            <!-- <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $adminTypeLabel }}</p> -->
            <div class="mt-0.5 flex items-center gap-2">
                <label class="shrink-0 text-[11px] font-medium uppercase tracking-wide text-gray-400">Branch</label>
                <x-admin.select
                    wrapper-class="relative inline-block min-w-[220px]"
                    class="rounded-xl border-gray-200 bg-slate-50 py-2 pl-3 pr-9 text-sm font-medium text-gray-800 {{ $lcTheme ? 'focus:border-brand-blue' : 'border-indigo-200 bg-indigo-50/60' }}"
                    x-model="branchId"
                    @change="switchBranch()"
                >
                    <option value="all" @selected($viewingAllBranches ?? false)>All branches</option>
                    @foreach ($allBranches as $branch)
                        <option value="{{ $branch->id }}" @selected(!($viewingAllBranches ?? false) && $activeBranch?->id === $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </x-admin.select>
            </div>
        @else
            <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Branch</p>
            <p class="truncate text-sm font-medium text-gray-900">{{ $activeBranch?->name ?? 'Unassigned' }}</p>
        @endif
    </div>

    @if ($lcTheme)
        <div class="pointer-events-none absolute inset-x-0 top-0 hidden h-16 items-center justify-center px-4 lg:flex">
            <div class="pointer-events-auto inline-flex max-w-[min(100%,28rem)] items-center gap-2 rounded-full border border-brand-yellow/40 bg-gradient-to-r from-brand-yellow/15 to-brand-yellow/5 px-4 py-1.5 shadow-sm">
                <span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-yellow text-[10px] font-bold text-brand-navy">✓</span>
                <p class="truncate text-sm font-semibold text-brand-navy">{{ config('libcontrol.defaults.admin_impact_text', 'Total 124+ Libraries Registered') }}</p>
            </div>
        </div>
    @else
        <div class="pointer-events-none absolute inset-x-0 top-0 hidden h-14 items-center justify-center px-4 lg:flex">
            <div class="pointer-events-auto inline-flex max-w-[min(100%,28rem)] items-center gap-2 rounded-full border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-1.5 shadow-sm">
                <span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-bold text-white">✓</span>
                <p class="truncate text-sm font-semibold text-emerald-900">{{ config('libcontrol.defaults.admin_impact_text', 'Total 124+ Libraries Registered') }}</p>
            </div>
        </div>
    @endif

    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
        <div
            class="relative"
            x-data="notificationBell({
                alerts: @js($recentAlerts),
                unreadCount: @js($alertCount),
                markReadUrl: @js(route('notifications.mark-read')),
                markAllUrl: @js(route('notifications.mark-all-read')),
                allUrl: @js(route('notifications.index')),
                pollUrl: @js(($isAnyAdmin ?? false) || ($isBranchStaff ?? false) ? route('notifications.feed') : null),
                pollIntervalMs: 15000,
                enableSound: @js(($isAnyAdmin ?? false) || ($isBranchStaff ?? false)),
            })"
            x-init="init()"
            @click.outside="open = false"
        >
            <button
                type="button"
                @click="open = !open"
                class="relative inline-flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-white"
                aria-label="Notifications"
            >
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span
                    x-show="unreadCount > 0"
                    class="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                    x-text="unreadCount > 9 ? '9+' : unreadCount"
                ></span>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition
                class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl sm:w-96"
            >
                <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-900">Recent Notifications</h3>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            x-show="unreadCount > 0"
                            @click="markAllRead()"
                            class="text-xs font-semibold text-gray-600 hover:text-gray-900"
                        >Mark all as read</button>
                        <a :href="allUrl" class="text-xs font-semibold {{ $lcTheme ? 'text-brand-blue hover:text-brand-navy' : 'text-indigo-600 hover:text-indigo-700' }}">View all</a>
                    </div>
                </div>
                <div class="max-h-80 divide-y divide-gray-100 overflow-y-auto">
                    <template x-for="alert in alerts" :key="alert.id">
                        <button type="button" class="block w-full px-4 py-3 text-left hover:bg-gray-50" @click="openAlert(alert)">
                            <div class="flex items-start gap-2">
                                <span class="mt-1 size-2 shrink-0 rounded-full" :class="alert.unread ? 'bg-amber-500' : 'bg-gray-200'"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900" x-text="alert.title"></p>
                                    <p class="mt-0.5 text-xs text-gray-600" x-text="alert.message"></p>
                                    <p class="mt-1 text-[11px] text-gray-400" x-text="alert.date"></p>
                                </div>
                            </div>
                        </button>
                    </template>
                    <p x-show="alerts.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">No recent notifications.</p>
                </div>
            </div>
        </div>

        @if ($lcTheme)
            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-brand-navy">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500">{{ $adminTypeLabel ?? 'Administrator' }}</p>
            </div>
        @endif

        <x-dropdown align="right" :width="$lcTheme ? 'w-72 min-w-[17rem]' : '48'">
            <x-slot name="trigger">
                <button type="button" class="inline-flex size-10 items-center justify-center rounded-full text-xs font-semibold text-white {{ $lcTheme ? 'bg-brand-blue ring-2 ring-brand-yellow/80 hover:bg-brand-navy' : 'bg-indigo-600 hover:bg-indigo-700' }}" aria-label="Account menu">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                    @if ($adminTypeLabel)
                        <p class="text-xs font-medium {{ $lcTheme ? 'text-brand-blue' : 'text-indigo-600' }}">{{ $adminTypeLabel }}</p>
                    @endif
                </div>

                @if ($lcTheme)
                    @php($profileMenuLinkClass = 'flex w-full items-center gap-3 whitespace-nowrap px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-100')
                    <a href="{{ route('profile.edit') }}" class="{{ $profileMenuLinkClass }}">
                        <svg class="size-[18px] shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>My Profile</span>
                    </a>
                    @if (\Illuminate\Support\Facades\Route::has('settings.index'))
                        <a href="{{ route('settings.index') }}" class="{{ $profileMenuLinkClass }}">
                            <svg class="size-[18px] shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Branch Settings</span>
                        </a>
                    @endif
                    @if (\Illuminate\Support\Facades\Route::has('help-support.index'))
                        <a href="{{ route('help-support.index') }}" class="{{ $profileMenuLinkClass }}">
                            <svg class="size-[18px] shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Help &amp; Support</span>
                        </a>
                    @endif
                    <div class="border-t border-gray-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="{{ $profileMenuLinkClass }} text-left">
                                <svg class="size-[18px] shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/></svg>
                                <span>Log out</span>
                            </button>
                        </form>
                    </div>
                @else
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                @endif
            </x-slot>
        </x-dropdown>
    </div>
</header>
