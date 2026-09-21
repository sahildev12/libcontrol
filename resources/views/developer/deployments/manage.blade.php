<x-admin-layout>
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Remote manage — {{ $deployment->client_name }}</h1>
                <p class="mt-1 text-sm text-gray-600">Self-hosted deployment. Actions are queued and delivered on the client's next sync heartbeat.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('developer.deployments.index', ['tab' => 'authorized', 'client' => $deployment->id]) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Domains &amp; license</a>
                <a href="{{ route('developer.deployments.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            This installation runs on the client&rsquo;s own server. Website and branch settings must be changed in that library&rsquo;s admin panel.
            @if (config('libcontrol.tenancy.enabled'))
                Use <a href="{{ route('developer.portals.index') }}" class="font-semibold text-sky-800 hover:underline">Portal Settings</a> for libraries hosted on Phenomit.
            @endif
        </div>

        @unless ($isOnline)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                This deployment has not checked in during the last 24 hours. Queued commands will run when the client comes online.
            </div>
        @endunless

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Deployment status</h2>
            </div>
            <dl class="grid gap-4 p-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Domains</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ implode(', ', $deployment->allowed_domains ?? []) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Last heartbeat</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $lastHeartbeat?->last_seen_at?->format('d M Y H:i') ?: 'Never' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Library code</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if ($registry?->library_code)
                            <code class="rounded bg-emerald-50 px-2 py-0.5 font-bold text-emerald-900">{{ $registry->library_code }}</code>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Current plan (hub)</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $planSnapshot['plan_label'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
            <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Subscription & limits</h2>
                <p class="mt-1 text-xs text-amber-800">Saved on the hub and pushed to the client via sync command.</p>
            </div>
            <form method="POST" action="{{ route('developer.deployments.manage.plan', $deployment) }}" class="grid gap-4 p-5 md:grid-cols-2">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Plan tier</label>
                    <select name="plan_tier" class="admin-select mt-1 block w-full px-3 py-2">
                        @foreach ($planTiers as $tier)
                            <option value="{{ $tier }}" @selected(old('plan_tier', $deployment->plan_tier ?: $planSnapshot['plan_tier']) === $tier)>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Custom seat limit</label>
                    <input type="number" min="1" name="max_seats_override" value="{{ old('max_seats_override', $deployment->max_seats_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Custom hall limit</label>
                    <input type="number" min="1" name="max_halls_override" value="{{ old('max_halls_override', $deployment->max_halls_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Custom branch limit</label>
                    <input type="number" min="1" name="max_branches_override" value="{{ old('max_branches_override', $deployment->max_branches_override) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Queue plan update</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Addons</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($availableAddons as $addon)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ $addon['name'] ?? $addon['slug'] }}</p>
                            <p class="text-xs text-gray-500">{{ $addon['slug'] }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('developer.deployments.manage.command', $deployment) }}">
                                @csrf
                                <input type="hidden" name="action" value="addon_install">
                                <input type="hidden" name="slug" value="{{ $addon['slug'] }}">
                                <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Install</button>
                            </form>
                            <form method="POST" action="{{ route('developer.deployments.manage.command', $deployment) }}">
                                @csrf
                                <input type="hidden" name="action" value="addon_enable">
                                <input type="hidden" name="slug" value="{{ $addon['slug'] }}">
                                <button type="submit" class="rounded-lg border border-green-300 bg-green-50 px-3 py-1.5 text-xs font-medium text-green-800 hover:bg-green-100">Enable</button>
                            </form>
                            <form method="POST" action="{{ route('developer.deployments.manage.command', $deployment) }}">
                                @csrf
                                <input type="hidden" name="action" value="addon_disable">
                                <input type="hidden" name="slug" value="{{ $addon['slug'] }}">
                                <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100">Disable</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500">No addons in the catalog.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Developer tools</h2>
            </div>
            <div class="flex flex-wrap gap-2 p-5">
                <form method="POST" action="{{ route('developer.deployments.manage.command', $deployment) }}">
                    @csrf
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Clear cache</button>
                </form>
                <form method="POST" action="{{ route('developer.deployments.manage.command', $deployment) }}">
                    @csrf
                    <input type="hidden" name="action" value="force_sync">
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Force sync ack</button>
                </form>
                <form method="POST" action="{{ route('developer.deployments.manage.command', $deployment) }}">
                    @csrf
                    <input type="hidden" name="action" value="database_backup">
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Database backup</button>
                </form>
                <form method="POST" action="{{ route('developer.deployments.manage.command', $deployment) }}">
                    @csrf
                    <input type="hidden" name="action" value="database_migrate">
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Run migrations</button>
                </form>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Command log</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Action</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Result</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Queued</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($commands as $command)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $command->action }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                    @if ($command->status === 'completed') bg-green-100 text-green-800
                                    @elseif ($command->status === 'failed') bg-red-100 text-red-800
                                    @elseif ($command->status === 'sent') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-700 @endif">
                                    {{ ucfirst($command->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $command->result ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $command->created_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No commands queued yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-admin-layout>
