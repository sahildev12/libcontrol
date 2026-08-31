<x-guest-layout
    :portal="$portal"
    :title="$title"
    :subtitle="$subtitle"
    :name="$name"
    :logo-url="$logo_url"
    :favicon-url="$favicon_url"
>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ $portal === 'admin' ? route('admin.login.store') : url('/login') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input id="password" name="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('password.request', $portal === 'admin' ? ['from' => 'admin'] : []) }}">
                {{ __('Forgot password?') }}
            </a>
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                {{ $portal === 'admin' ? 'Admin log in' : 'Branch log in' }}
            </x-primary-button>
        </div>

        <p class="mt-4 text-center text-xs text-gray-500">
            @if ($portal === 'admin')
                Branch staff?
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Use branch login</a>
            @else
                <!-- Platform admin? -->
                <!-- <a href="{{ route('admin.login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Use admin login</a> -->
            @endif
        </p>
    </form>
</x-guest-layout>
