<x-guest-layout
    portal="auth"
    title="Verify your email to continue"
    subtitle="{{ $tenant->host() }} is paused until the owner's email is confirmed"
>
    <div class="space-y-4 text-sm text-gray-600" data-trial-verify-required>
        <p>
            We sent a verification link to <span class="font-semibold text-gray-900">{{ $tenant->owner_email }}</span>.
            Open it and this library unlocks straight away. Your data is safe.
        </p>

        @if (session('trial_notice'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('trial_notice') }}</div>
        @endif

        <form method="POST" action="{{ route('trials.resend-verification') }}">
            @csrf
            <x-primary-button class="w-full justify-center">Send the link again</x-primary-button>
        </form>

        <p class="text-xs text-gray-500">
            Can't find it? Check your spam folder, or email
            <a href="mailto:{{ config('libcontrol.support.public_email') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ config('libcontrol.support.public_email') }}</a>.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-xs font-semibold text-gray-500 underline hover:text-gray-700">Log out</button>
        </form>
    </div>
</x-guest-layout>
