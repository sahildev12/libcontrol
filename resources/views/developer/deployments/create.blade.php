<x-admin-layout>
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add deployment</h1>
            <p class="mt-1 text-sm text-gray-600">Create a whitelist entry and issue a new license key.</p>
        </div>

        <form method="POST" action="{{ route('developer.deployments.store') }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700" for="client_name">Client name</label>
                <input id="client_name" name="client_name" value="{{ old('client_name') }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('client_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="allowed_domains">Allowed domains</label>
                <textarea id="allowed_domains" name="allowed_domains" rows="4" required placeholder="library.client.com&#10;www.library.client.com" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('allowed_domains') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">One domain per line or comma-separated.</p>
                @error('allowed_domains') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="grace_days">Grace days (unauthorized)</label>
                <input id="grace_days" name="grace_days" type="number" min="0" max="90" value="{{ old('grace_days', 7) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="flex items-center gap-2">
                <input id="active" name="active" type="checkbox" value="1" @checked(old('active', true)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="active" class="text-sm text-gray-700">Active</label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Create & issue key</button>
                <a href="{{ route('developer.deployments.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</x-admin-layout>
