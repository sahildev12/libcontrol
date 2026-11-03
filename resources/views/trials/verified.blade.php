<x-guest-layout
    portal="auth"
    :title="$valid ? 'Email verified' : 'This link has expired'"
    :subtitle="$valid ? 'Thanks! '.$tenant->host().' is fully active.' : 'Verification links work for 7 days.'"
>
    <div class="space-y-4 text-sm text-gray-600" data-trial-verified="{{ $valid ? 'yes' : 'no' }}">
        @if ($valid)
            <p>Your email address is confirmed. You can carry on setting up your library.</p>
            <a href="{{ $libraryUrl }}" class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Open my library
            </a>
        @else
            <p>
                Log in to <a href="{{ $libraryUrl }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ $tenant->host() }}</a>
                and use <span class="font-semibold">Send the link again</span> to get a fresh link, or email
                <a href="mailto:{{ config('libcontrol.support.public_email') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ config('libcontrol.support.public_email') }}</a>.
            </p>
        @endif
    </div>
</x-guest-layout>
