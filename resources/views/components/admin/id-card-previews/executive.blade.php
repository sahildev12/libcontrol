<article class="relative w-full overflow-hidden rounded-lg bg-slate-950 shadow-md ring-1 ring-amber-600/30" style="aspect-ratio: 86/54;">
    <div class="h-[3px] bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600"></div>

    <div class="flex items-center justify-between px-3 py-2">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="LibControl" class="h-5 max-w-[30%] object-contain brightness-0 invert">
        @else
            <p class="text-[8px] font-bold uppercase tracking-[0.16em] text-amber-300">LibControl</p>
        @endif
        <p class="text-[6px] font-semibold uppercase tracking-[0.2em] text-amber-400/80">Official library ID</p>
    </div>

    <div class="mx-3 h-px bg-gradient-to-r from-transparent via-amber-500/50 to-transparent"></div>

    <div class="flex flex-col px-3 pb-2.5 pt-1">
        <div class="flex gap-2.5">
            <div class="aspect-[3/4] w-[22%] shrink-0 overflow-hidden rounded-md ring-2 ring-amber-500/40">
                @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-md'])
            </div>

            <div class="flex min-w-0 flex-1 flex-col justify-between">
                <div>
                    <h3 class="truncate text-sm font-bold leading-tight text-white">{{ $sample['name'] }}</h3>
                    <p class="font-mono text-[10px] font-semibold text-amber-400">{{ $sample['student_id'] }}</p>
                </div>

                <dl class="grid grid-cols-2 gap-x-2 gap-y-0.5 text-[7px] leading-tight">
                    <div>
                        <dt class="uppercase tracking-wide text-slate-500">Course</dt>
                        <dd class="font-semibold text-slate-200">{{ $sample['course'] }}</dd>
                    </div>
                    <div>
                        <dt class="uppercase tracking-wide text-slate-500">Branch</dt>
                        <dd class="font-semibold text-slate-200">{{ $sample['branch'] }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="uppercase tracking-wide text-slate-500">Valid till</dt>
                        <dd class="font-semibold text-slate-200">{{ $sample['valid_till'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex shrink-0 flex-col items-center justify-center gap-1">
                @include('components.admin.id-card-previews._qr', ['class' => 'size-10 ring-1 ring-amber-500/30'])
                <p class="text-[5px] text-amber-500/70">Verify</p>
            </div>
        </div>

        <div class="mt-1.5 space-y-0.5">
            @include('components.admin.id-card-previews._barcode', ['class' => 'h-3 w-full', 'light' => true])
        </div>
    </div>
</article>
