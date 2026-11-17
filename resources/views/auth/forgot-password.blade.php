<x-guest-layout
    :portal="$portal"
    :title="$title"
    :subtitle="$subtitle"
    :name="$name"
    :logo-url="$logo_url"
    :favicon-url="$favicon_url"
    :favicon-custom="$favicon_custom"
    robots="noindex, nofollow"
>
    <p class="mb-4 text-sm text-gray-600">{{ $resetIntro }}</p>
    <p class="mb-4 text-xs text-gray-500">{{ config('libcontrol.product.byline') }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <span class="text-sm text-gray-600">
                <a href="{{ route('admin.login') }}" class="hover:text-gray-900">Owner login</a>
                <span aria-hidden="true"> · </span>
                <a href="{{ route('login') }}" class="hover:text-gray-900">Branch login</a>
            </span>
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
