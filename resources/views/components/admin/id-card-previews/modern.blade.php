<article class="relative w-full overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-slate-200" style="aspect-ratio: 86/54;">
    <header class="flex h-[22%] items-center justify-between bg-gradient-to-r from-indigo-600 to-violet-600 px-3 text-white">
        <div class="min-w-0 flex-1">
            <p class="truncate text-[7px] font-bold uppercase tracking-[0.14em]">{{ $sample['branch'] }}</p>
            <p class="text-[5px] text-indigo-100">Student identity card</p>
        </div>
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="" class="h-5 max-w-[28%] object-contain brightness-0 invert">
        @endif
    </header>

    <div class="flex h-[78%] gap-2 px-3 py-2">
        <div class="w-[22%] shrink-0 overflow-hidden rounded-full ring-2 ring-indigo-100">
            @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-full'])
        </div>

        <div class="flex min-w-0 flex-1 flex-col justify-between py-0.5">
            <div>
                <h3 class="truncate text-sm font-bold leading-tight text-slate-900">{{ $sample['name'] }}</h3>
                <p class="font-mono text-[10px] font-semibold text-indigo-700">{{ $sample['student_id'] }}</p>
                <span class="mt-1 inline-flex rounded-full bg-indigo-50 px-1.5 py-0.5 text-[5px] font-semibold uppercase tracking-wide text-indigo-700">Regular</span>
            </div>

            @include('components.admin.id-card-previews._meta-grid')
        </div>

        <div class="flex w-[14%] shrink-0 flex-col items-center justify-center gap-1">
            @include('components.admin.id-card-previews._qr', ['class' => 'size-11'])
            <p class="text-[5px] text-slate-400">Scan</p>
        </div>
    </div>
</article>
