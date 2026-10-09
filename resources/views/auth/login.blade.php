<x-guest-layout
    :portal="$portal"
    :title="$title"
    :subtitle="$subtitle"
    :name="$name"
    :logo-url="$logo_url"
    :favicon-url="$favicon_url"
    :favicon-custom="$favicon_custom"
>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $demoLogin = config('libcontrol.demo.enabled') && $portal !== 'developer'
            ? ($portal === 'admin'
                ? ['label' => 'Library admin', 'email' => config('libcontrol.demo.admin_email'), 'password' => config('libcontrol.demo.admin_password')]
                : ['label' => 'Branch staff', 'email' => config('libcontrol.demo.branch_email'), 'password' => config('libcontrol.demo.branch_password')])
            : null;
    @endphp

    @if ($demoLogin)
        <div
            class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm"
            x-data="{ fill() { document.getElementById('email').value = @js($demoLogin['email']); document.getElementById('password').value = @js($demoLogin['password']); } }"
            data-demo-login
        >
            <div class="flex items-center justify-between gap-3">
                <p class="font-semibold text-amber-900">Demo login &middot; {{ $demoLogin['label'] }}</p>
                <button type="button" x-on:click="fill()" class="rounded-lg bg-[#243a8b] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1c2e70]">
                    Use demo login
                </button>
            </div>
            <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-amber-900">
                <dt class="text-amber-700">Email</dt>
                <dd class="break-all font-mono select-all">{{ $demoLogin['email'] }}</dd>
                <dt class="text-amber-700">Password</dt>
                <dd class="font-mono select-all">{{ $demoLogin['password'] }}</dd>
            </dl>
            <p class="mt-2 text-xs text-amber-700">
                @if ($portal === 'admin')
                    Want to see the branch staff view? <a href="{{ route('login') }}" class="font-semibold underline">Branch login</a>
                @else
                    Want to see the owner view? <a href="{{ route('admin.login') }}" class="font-semibold underline">Admin login</a>
                @endif
            </p>
        </div>
    @endif

    <form method="POST" action="{{ match($portal) {
        'developer' => route('developer.login.store'),
        'admin' => route('admin.login.store'),
        default => route('login.store'),
    } }}">
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
                {{ match($portal) {
                    'developer' => 'Developer log in',
                    'admin' => 'Admin log in',
                    default => 'Branch log in',
                } }}
            </x-primary-button>
        </div>

        <p class="mt-4 text-center text-xs text-gray-500">
            @if ($portal === 'developer')
                Library admin?
                <a href="{{ route('admin.login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Use admin login</a>
            @elseif ($portal === 'admin')
                Branch staff?
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Use branch login</a>
            @else
                Library admin?
                <a href="{{ route('admin.login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Use admin login</a>
            @endif
        </p>
    </form>
</x-guest-layout>
