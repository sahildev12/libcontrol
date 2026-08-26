<x-guest-layout
    :portal="$portal"
    :title="$title"
    :subtitle="$subtitle"
    :name="$name"
    :logo-url="$logo_url"
    :favicon-url="$favicon_url"
>
    <p class="mb-4 text-sm text-gray-600">
        Enter the email for this {{ $portal === 'admin' ? 'admin' : 'branch' }} account. We will send a reset link if it matches.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ $portal === 'admin' ? route('admin.password.email') : route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <a href="{{ $portal === 'admin' ? route('admin.login') : route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Back to login</a>
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
