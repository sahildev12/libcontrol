@php
    $demoBranchLogin = 'https://demo.'.$baseDomain.'/branch/login';
@endphp
<x-guest-layout
    portal="branch"
    title="Branch staff log in"
    subtitle="Step 1: find your library. Step 2: enter email & password there."
>
    <form method="GET" action="#" class="space-y-4" x-data="{ subdomain: '' }" @submit.prevent="
        const s = subdomain.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
        if (!s) return;
        window.location.href = 'https://' + s + '.{{ $baseDomain }}/branch/login';
    ">
        <div>
            <x-input-label for="subdomain" value="Your library address" />
            <div class="mt-1 flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                <input
                    id="subdomain"
                    type="text"
                    x-model="subdomain"
                    required
                    autocomplete="off"
                    placeholder="yourlibrary"
                    class="min-w-0 flex-1 border-0 p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0"
                >
                <span class="shrink-0 text-sm text-gray-500">.{{ $baseDomain }}</span>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                After you continue, you’ll sign in with your branch email and password on that library.
            </p>
        </div>

        <button type="submit" class="lc-web-btn lc-web-btn--yellow w-full uppercase tracking-wide">
            Continue to branch login
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>

    <p class="mt-5 text-center text-xs text-gray-500">
        Library owner?
        <a href="{{ route('admin.login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Use admin login</a>
        ·
        <a href="{{ $demoBranchLogin }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Open demo</a>
    </p>
</x-guest-layout>
