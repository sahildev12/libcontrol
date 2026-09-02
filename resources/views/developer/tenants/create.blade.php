<x-admin-layout>
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add client library</h1>
            <p class="mt-1 text-sm text-gray-600">Create the MySQL database in Hostinger first, then provision the subdomain here.</p>
        </div>

        <form method="POST" action="{{ route('developer.tenants.store') }}" class="space-y-6">
            @csrf

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Client details</h2>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Business name</label>
                        <input type="text" name="client_name" value="{{ old('client_name') }}" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subdomain</label>
                        <div class="mt-1 flex">
                            <input type="text" name="subdomain" value="{{ old('subdomain') }}" required pattern="[a-z0-9]([a-z0-9-]*[a-z0-9])?" class="block w-full rounded-l-lg border border-gray-300 px-3 py-2 text-sm">
                            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600">.{{ $baseDomain }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Database name</label>
                        <input type="text" name="database_name" id="tenant-database-name" value="{{ old('database_name') }}" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Create this empty database in Hostinger first, then click prepare.</p>
                    </div>
                    <div class="md:col-span-2">
                        <button
                            type="button"
                            id="prepare-database-btn"
                            data-url="{{ route('developer.tenants.prepare-database') }}"
                            class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                        >
                            Prepare database (auto-migrate)
                        </button>
                        <p id="prepare-database-status" class="mt-2 text-sm"></p>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Plan</h2>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Plan tier</label>
                        <select name="plan_tier" class="admin-select mt-1 block w-full px-3 py-2">
                            @foreach ($planTiers as $tier)
                                <option value="{{ $tier }}" @selected(old('plan_tier', 'starter') === $tier)>{{ ucfirst($tier) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custom seat limit</label>
                        <input type="number" min="1" name="max_seats_override" value="{{ old('max_seats_override') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custom hall limit</label>
                        <input type="number" min="1" name="max_halls_override" value="{{ old('max_halls_override') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custom branch limit</label>
                        <input type="number" min="1" name="max_branches_override" value="{{ old('max_branches_override') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">First admin login</h2>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Admin name</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name', 'Library Admin') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Admin email</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Admin password</label>
                        <input type="password" name="admin_password" required minlength="8" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </section>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <a href="{{ route('developer.tenants.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700">Cancel</a>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Create client library</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('prepare-database-btn')?.addEventListener('click', async () => {
            const button = document.getElementById('prepare-database-btn');
            const status = document.getElementById('prepare-database-status');
            const databaseName = document.getElementById('tenant-database-name')?.value;

            if (!databaseName) {
                status.className = 'mt-2 text-sm text-red-700';
                status.textContent = 'Enter a database name first.';
                return;
            }

            button.disabled = true;
            status.className = 'mt-2 text-sm text-gray-600';
            status.textContent = 'Migrating database tables...';

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({ database_name: databaseName }),
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Database preparation failed.');
                }

                status.className = 'mt-2 text-sm text-green-700';
                status.textContent = data.message;
            } catch (error) {
                status.className = 'mt-2 text-sm text-red-700';
                status.textContent = error.message || 'Database preparation failed.';
            } finally {
                button.disabled = false;
            }
        });
    </script>
</x-admin-layout>
