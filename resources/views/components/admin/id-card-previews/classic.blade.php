<article class="relative w-full overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-slate-200" style="aspect-ratio: 86/54;">
    <div class="flex h-full">
        <aside class="flex w-[26%] flex-col items-center justify-between bg-[#4f46e5] px-2 py-3 text-white">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="h-5 max-w-[90%] object-contain brightness-0 invert">
            @else
                <p class="text-center text-[7px] font-bold uppercase tracking-[0.16em]">LibControl</p>
            @endif

            <div class="w-[78%] overflow-hidden rounded-md ring-2 ring-white/30">
                @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-md'])
            </div>

            <p class="text-[6px] font-semibold uppercase tracking-widest text-indigo-100">Student</p>
        </aside>

        <section class="relative flex min-w-0 flex-1 flex-col justify-between px-3 py-2.5">
            @include('components.admin.id-card-previews._watermark')

            <header>
                <p class="text-[6px] font-semibold uppercase tracking-[0.18em] text-indigo-600">Student identity card</p>
                <h3 class="mt-0.5 text-sm font-bold leading-tight text-slate-900">{{ $sample['name'] }}</h3>
                <p class="font-mono text-[10px] font-semibold text-indigo-700">{{ $sample['student_id'] }}</p>
            </header>

            @include('components.admin.id-card-previews._meta-grid')

            <footer class="space-y-0.5">
                @include('components.admin.id-card-previews._barcode', ['class' => 'h-3.5 w-full'])
                <p class="text-[5px] text-slate-400">Present at library entry</p>
            </footer>
        </section>
    </div>
</article>
