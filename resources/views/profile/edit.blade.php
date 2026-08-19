<x-admin-layout>
    <header>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Profile') }}</h1>
        <p class="mt-1 text-sm text-gray-600">Manage your account settings.</p>
    </header>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 sm:p-8 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 sm:p-8 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white p-4 sm:p-8 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
</x-admin-layout>
