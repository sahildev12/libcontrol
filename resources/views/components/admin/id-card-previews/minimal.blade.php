<article class="relative w-full overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-slate-200" style="aspect-ratio: 86/54;">
    <div class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></div>

    <div class="flex h-full pl-3 pr-2.5">
        <div class="flex w-[20%] flex-col items-center justify-center gap-2 py-3">
            <div class="w-full overflow-hidden rounded-full ring-2 ring-emerald-100">
                @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-full'])
            </div>
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="LibControl" class="h-4 max-w-full object-contain opacity-70">
            @endif
        </div>

        <div class="flex min-w-0 flex-1 flex-col justify-between py-3 pl-2">
            <div>
                <p class="text-[6px] font-semibold uppercase tracking-[0.22em] text-emerald-600">LibControl</p>
                <h3 class="mt-0.5 text-[13px] font-bold leading-tight text-slate-900">{{ $sample['name'] }}</h3>
                <p class="font-mono text-[9px] font-semibold text-slate-600">{{ $sample['student_id'] }}</p>
            </div>

            <dl class="space-y-1 text-[7px] leading-tight">
                <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                    <dt class="text-slate-400">Course</dt>
                    <dd class="font-semibold text-slate-800">{{ $sample['course'] }}</dd>
                </div>
                <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                    <dt class="text-slate-400">Branch</dt>
                    <dd class="font-semibold text-slate-800">{{ $sample['branch'] }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-slate-400">Valid till</dt>
                    <dd class="font-semibold text-slate-800">{{ $sample['valid_till'] }}</dd>
                </div>
            </dl>

            <p class="text-[6px] text-slate-400">Good readers make great leaders</p>
        </div>

        <div class="flex w-[14%] shrink-0 flex-col items-center justify-center">
            @include('components.admin.id-card-previews._qr', ['class' => 'size-10'])
        </div>
    </div>
</article>
