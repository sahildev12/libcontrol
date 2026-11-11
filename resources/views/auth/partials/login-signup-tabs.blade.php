@php
    /** @var 'login'|'signup' $active */
    $active = $active ?? 'login';
    $loginHref = $loginHref ?? null;
    $signupHref = $signupHref ?? null;
    $tabBase = 'flex min-h-10 items-center justify-center rounded-lg px-3 py-2 text-center text-sm font-semibold outline-none transition focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1';
    $tabOn = 'bg-white text-gray-900 shadow-sm';
    $tabOff = 'text-gray-500 hover:text-gray-800';
@endphp
<div class="mb-5 grid grid-cols-2 gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1" role="tablist" aria-label="Account">
    @if ($loginHref)
        <a
            href="{{ $loginHref }}"
            role="tab"
            aria-selected="{{ $active === 'login' ? 'true' : 'false' }}"
            class="{{ $tabBase }} {{ $active === 'login' ? $tabOn : $tabOff }}"
        >Log in</a>
    @else
        <button
            type="button"
            role="tab"
            :aria-selected="authMode === 'login' ? 'true' : 'false'"
            @click="authMode = 'login'"
            :class="authMode === 'login' ? '{{ $tabOn }}' : '{{ $tabOff }}'"
            class="{{ $tabBase }}"
        >Log in</button>
    @endif

    @if ($signupHref)
        <a
            href="{{ $signupHref }}"
            role="tab"
            aria-selected="{{ $active === 'signup' ? 'true' : 'false' }}"
            class="{{ $tabBase }} {{ $active === 'signup' ? $tabOn : $tabOff }}"
        >Sign up</a>
    @else
        <button
            type="button"
            role="tab"
            :aria-selected="authMode === 'signup' ? 'true' : 'false'"
            @click="authMode = 'signup'"
            :class="authMode === 'signup' ? '{{ $tabOn }}' : '{{ $tabOff }}'"
            class="{{ $tabBase }}"
        >Sign up</button>
    @endif
</div>
