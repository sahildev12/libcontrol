<x-admin-layout>
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="lc-page-title">Manage {{ $tenant->client_name }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                <a href="{{ $tenant->url() }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-700">{{ $tenant->host() }}</a>
                · Database: {{ $tenant->database_name }}
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('developer.tenants.update', $tenant) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Client details</h2>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Business name</label>
                        <input type="text" name="client_name" value="{{ old('client_name', $tenant->client_name) }}" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subdomain</label>
                        <input type="text" value="{{ $tenant->host() }}" disabled class="mt-1 block w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="active" value="1" @checked(old('active', $tenant->active)) class="rounded border-gray-300 text-indigo-600">
                            Active
                        </label>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
                <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Plan & limits</h2>
                    <p class="mt-1 text-xs text-amber-800">Changes sync to this client's isolated database immediately.</p>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Plan tier</label>
                        <select name="plan_tier" class="admin-select mt-1 block w-full px-3 py-2">
                            @foreach ($planTiers as $tier)
                                <option value="{{ $tier }}" @selected(old('plan_tier', $tenant->plan_tier) === $tier)>{{ ucfirst($tier) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custom seat limit</label>
                        <input type="number" min="1" name="max_seats_override" value="{{ old('max_seats_override', $tenant->max_seats_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custom hall limit</label>
                        <input type="number" min="1" name="max_halls_override" value="{{ old('max_halls_override', $tenant->max_halls_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custom branch limit</label>
                        <input type="number" min="1" name="max_branches_override" value="{{ old('max_branches_override', $tenant->max_branches_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </section>

            @if ($tenant->is_trial)
                <section class="overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm" data-trial-controls>
                    <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Free trial</h2>
                        <p class="mt-1 text-xs text-indigo-800">
                            Owner {{ $tenant->owner_email }} ·
                            {{ $tenant->owner_email_verified_at ? 'email verified' : 'email not verified' }} ·
                            @if ($tenant->locked_at)
                                read-only since {{ $tenant->locked_at->format('d M Y') }}, deleted on {{ $tenant->deletesAt()?->format('d M Y') }} unless upgraded
                            @else
                                ends {{ $tenant->trial_ends_at?->format('d M Y') ?? 'not set' }}
                            @endif
                        </p>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trial ends on</label>
                            <input type="date" name="trial_ends_at" value="{{ old('trial_ends_at', $tenant->trial_ends_at?->toDateString()) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <p class="mt-1 text-xs text-gray-500">A future date unlocks a read-only trial.</p>
                        </div>
                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" name="end_trial" value="1" class="rounded border-gray-300 text-indigo-600">
                                Paid: end the trial and unlock for good
                            </label>
                            @unless ($tenant->owner_email_verified_at)
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <input type="checkbox" name="owner_email_verified" value="1" class="rounded border-gray-300 text-indigo-600">
                                    Mark the owner's email as verified
                                </label>
                            @endunless
                        </div>
                    </div>
                </section>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('notes', $tenant->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('developer.tenants.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700">Back</a>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Save changes</button>
            </div>
        </form>
    </div>
</x-admin-layout>
