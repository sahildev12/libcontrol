@php
    $isSuccess = ($title ?? '') === 'Student Registered Successfully';
@endphp

<x-students.registration-shell :page-title="$title">
    <div class="px-6 py-10 text-center">
        <div @class([
            'mx-auto inline-flex size-14 items-center justify-center rounded-full',
            'bg-emerald-100 text-emerald-700' => $isSuccess,
            'bg-amber-100 text-amber-700' => ! $isSuccess,
        ])>
            @if ($isSuccess)
                <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            @else
                <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            @endif
        </div>
        <h1 class="mt-4 text-xl font-bold text-brand-navy">{{ $title }}</h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">{{ $message }}</p>

        @if ($isSuccess)
            <div class="mt-8 flex flex-col items-center gap-2">
                <button
                    type="button"
                    id="close-registration-tab"
                    class="inline-flex items-center justify-center rounded-xl bg-brand-blue px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-navy focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:ring-offset-2"
                >
                    Close this tab
                </button>
                <p class="text-xs text-gray-500">You can also close this browser tab manually.</p>
            </div>
            <script>
                document.getElementById('close-registration-tab')?.addEventListener('click', () => {
                    window.close();
                    window.setTimeout(() => {
                        if (!window.closed) {
                            document.getElementById('close-registration-tab')?.blur();
                        }
                    }, 200);
                });
            </script>
        @endif
    </div>
</x-students.registration-shell>
