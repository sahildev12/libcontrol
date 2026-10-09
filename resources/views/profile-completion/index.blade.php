<x-admin-layout>
    @php
        $pct = (int) ($completion['pct'] ?? 0);
        $items = $completion['items'] ?? [];
        $doneCount = collect($items)->where('status', 'ok')->count();
        $totalCount = count($items);
        $complete = (bool) ($completion['complete'] ?? false);
    @endphp

    <div class="mx-auto max-w-3xl space-y-5">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <span
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                    style="background-color:#082a67;"
                >1</span>
                <div>
                    <h1 class="text-xl font-bold tracking-tight" style="color:#082a67;">Profile Completion (Business Setup)</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Complete your library profile and set up all amenities to get 100% profile completion.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('profile-completion.continue') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Continue to dashboard
                </button>
            </form>
        </header>

        <section
            class="overflow-hidden rounded-2xl border bg-white shadow-sm"
            style="border-color:#DCE6F5;"
        >
            <div class="space-y-4 p-5" style="background-color:#F4F8FF;">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold" style="color:#082a67;">Profile Completion</h2>
                    <p class="text-sm font-semibold text-slate-500">{{ $doneCount }}/{{ $totalCount }} completed</p>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="relative h-3 min-w-0 flex-1 overflow-hidden rounded-full"
                        style="background-color:#FFCC00;"
                        role="progressbar"
                        aria-valuenow="{{ $pct }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Profile completion {{ $pct }} percent"
                    >
                        <span
                            class="absolute inset-y-0 left-0 rounded-full"
                            style="width:{{ $pct }}%;background-color:#082a67;"
                        ></span>
                    </span>
                    <span class="shrink-0 text-lg font-bold tabular-nums" style="color:#082a67;">{{ $pct }}%</span>
                </div>
            </div>

            <ul class="divide-y divide-slate-100 px-2 py-1">
                @foreach ($items as $item)
                    @php
                        $ok = ($item['status'] ?? '') === 'ok';
                    @endphp
                    <li>
                        <a
                            @if (! empty($item['href'])) href="{{ $item['href'] }}" @endif
                            class="flex items-start gap-3 rounded-xl px-3 py-3.5 transition hover:bg-slate-50"
                            style="text-decoration:none;"
                        >
                            <span
                                class="mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-full text-white"
                                style="background-color:{{ $ok ? '#22C55E' : '#F87171' }};"
                            >
                                @if ($ok)
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold" style="color:#082a67;">{{ $item['label'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $item['detail'] }}</p>
                            </div>
                            <svg class="mt-1 size-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($complete)
                <div class="m-4 flex items-center gap-3 rounded-xl px-4 py-3" style="background-color:#ECFDF5;">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-emerald-800">Profile Complete!</p>
                        <p class="text-xs text-emerald-700">Your library is fully set up.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
