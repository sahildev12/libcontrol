@php
    $settings = \App\Models\PlatformSetting::current();
    $supportPhone = (string) config('libcontrol.support.phone');
    $supportEmail = (string) config('libcontrol.support.email');
@endphp
<x-guest-layout
    portal="branch"
    title="Subscription ended"
    :subtitle="$settings->displayName()"
    :name="$settings->displayName()"
    :logo-url="$settings->logoUrl()"
    :favicon-url="$settings->faviconUrl()"
    :favicon-custom="$settings->hasCustomFavicon()"
>
    <div class="flex items-start gap-3">
        <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <div>
            <h2 class="text-lg font-bold text-brand-navy">LibControl is paused</h2>
            <p class="mt-0.5 text-sm text-gray-600">
                The subscription
                @if ($paidUntil)
                    ended on <span class="font-semibold text-gray-900">{{ $paidUntil->format('d M Y') }}</span>.
                @else
                    has ended.
                @endif
                All your data is safe.
            </p>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-brand-navy">How to renew</p>
        <ol class="mt-3 space-y-2.5 text-sm text-gray-700">
            @foreach ([
                'The library owner logs in with the <strong>Admin login</strong>.',
                '<strong>Subscription &amp; Billing</strong> opens automatically. Pick a plan.',
                'Pay with UPI, card or net banking. LibControl unlocks for everyone right away.',
            ] as $step)
                <li class="flex gap-3">
                    <span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-yellow text-xs font-bold text-brand-navy">{{ $loop->iteration }}</span>
                    <span class="pt-0.5">{!! $step !!}</span>
                </li>
            @endforeach
        </ol>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-5">
        @csrf
        <input type="hidden" name="renew" value="1">
        <x-primary-button class="w-full justify-center">I'm the owner &middot; Log in to renew</x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-2">
        @csrf
        <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Log out
        </button>
    </form>

    @if ($supportPhone !== '' || $supportEmail !== '')
        <p class="mt-5 border-t border-gray-100 pt-4 text-center text-xs text-gray-500">
            Paying offline or need help? Contact Katsindia
            @if ($supportPhone !== '')
                &middot; <a href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ $supportPhone }}</a>
            @endif
            @if ($supportEmail !== '')
                &middot; <a href="mailto:{{ $supportEmail }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ $supportEmail }}</a>
            @endif
        </p>
    @endif
</x-guest-layout>
