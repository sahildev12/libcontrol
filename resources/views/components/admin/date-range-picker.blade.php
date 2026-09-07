@props([
    'action',
    'label',
    'fromName' => 'date_from',
    'toName' => 'date_to',
    'fromValue' => '',
    'toValue' => '',
])

<div class="relative" @click.outside="rangeOpen = false">
    <div
        x-show="rangeOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-40 bg-gray-900/30 lg:hidden"
        @click="rangeOpen = false"
    ></div>

    <button
        type="button"
        @click="rangeOpen = !rangeOpen"
        {{ $attributes->merge(['class' => 'inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50']) }}
    >
        <svg class="size-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span class="truncate">{{ $label }}</span>
    </button>

    <form
        method="get"
        action="{{ $action }}"
        x-show="rangeOpen"
        x-cloak
        x-transition
        @click.stop
        class="fixed left-1/2 top-1/2 z-50 w-[min(18rem,calc(100vw-2rem))] -translate-x-1/2 -translate-y-1/2 rounded-xl border border-gray-200 bg-white p-4 shadow-xl lg:absolute lg:left-auto lg:right-0 lg:top-full lg:mt-2 lg:w-72 lg:translate-x-0 lg:translate-y-0"
    >
        {{ $slot }}

        <div class="space-y-3">
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">From</label>
                <input type="date" name="{{ $fromName }}" value="{{ $fromValue }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-500">To</label>
                <input type="date" name="{{ $toName }}" value="{{ $toValue }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="submit" class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">
                Apply range
            </button>
        </div>
    </form>
</div>
