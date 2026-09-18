<article class="id-card relative overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200" style="width: 86mm; height: 54mm;">
    <header class="flex h-[12mm] items-center justify-between bg-gradient-to-r from-indigo-600 to-violet-600 px-3 text-white">
        <div class="min-w-0">
            <p class="truncate text-[8px] font-bold uppercase tracking-[0.14em]">{{ $branchName }}</p>
            <p class="text-[6px] text-indigo-100">Student identity card</p>
        </div>
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="" class="h-[7mm] max-w-[22mm] object-contain brightness-0 invert">
        @endif
    </header>

    <div class="flex h-[calc(54mm-12mm)] gap-2 px-3 py-2">
        <div class="w-[19mm] shrink-0">
            <div class="overflow-hidden rounded-full ring-2 ring-indigo-100">
                @include('students.id-cards._photo', [
                    'class' => 'size-[17mm] object-cover',
                    'fallbackClass' => 'flex size-[17mm] items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-800',
                ])
            </div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col justify-between py-0.5">
            <div>
                <h1 class="truncate text-[12px] font-bold leading-tight text-slate-900">{{ $student->name }}</h1>
                <p class="font-mono text-[10px] font-semibold text-indigo-700">{{ $student->student_code }}</p>
                <span class="mt-1 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[6px] font-semibold uppercase tracking-wide text-indigo-700">{{ $student->typeLabel() }}</span>
            </div>

            @include('students.id-cards._meta-grid')
        </div>

        <div class="flex w-[14mm] shrink-0 flex-col items-center justify-center gap-1">
            @include('components.admin.id-card-previews._qr', ['class' => 'size-[13mm]'])
            <p class="text-[5px] font-medium uppercase tracking-wide text-slate-400">Scan</p>
        </div>
    </div>
</article>
