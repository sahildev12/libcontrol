<x-admin-layout>
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit deployment</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $deployment->client_name }}</p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if (session('issued_license_key'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">License key (copy now):</p>
                <code class="mt-2 block break-all rounded bg-white px-3 py-2 text-xs">{{ session('issued_license_key') }}</code>
                <p class="mt-2 text-xs">Set as <code>LIBCONTROL_LICENSE_KEY</code> in the client server .env file.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('developer.deployments.update', $deployment) }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-sm font-medium text-gray-700" for="client_name">Client name</label>
                <input id="client_name" name="client_name" value="{{ old('client_name', $deployment->client_name) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="allowed_domains">Allowed domains</label>
                <textarea id="allowed_domains" name="allowed_domains" rows="4" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('allowed_domains', $domainsText) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="grace_days">Grace days</label>
                <input id="grace_days" name="grace_days" type="number" min="0" max="90" value="{{ old('grace_days', $deployment->grace_days) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="flex items-center gap-2">
                <input id="active" name="active" type="checkbox" value="1" @checked(old('active', $deployment->active)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="active" class="text-sm text-gray-700">Active</label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $deployment->notes) }}</textarea>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save changes</button>
                <a href="{{ route('developer.deployments.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</a>
            </div>
        </form>

        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('developer.deployments.regenerate-key', $deployment) }}" onsubmit="return confirm('Issue a new license key? The old key will stop working.');">
                @csrf
                <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100">Regenerate license key</button>
            </form>
            <form method="POST" action="{{ route('developer.deployments.destroy', $deployment) }}" onsubmit="return confirm('Delete this deployment?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">Delete</button>
            </form>
        </div>
    </div>
</x-admin-layout>
