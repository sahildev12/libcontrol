<article class="id-card relative w-[86mm] overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-300" style="height: 54mm;">
    <div class="bg-slate-900 px-3 py-2 text-white">
        <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
                <p class="truncate text-[9px] font-bold uppercase tracking-[0.16em] text-amber-300">{{ $branchName }}</p>
                <p class="text-[7px] text-slate-300">Official library ID</p>
            </div>
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="h-[7mm] max-w-[20mm] object-contain">
            @endif
        </div>
    </div>
    <div class="flex gap-2.5 px-3 py-2">
        @if ($student->photoUrl())
            <img src="{{ $student->photoUrl() }}" alt="" class="size-[17mm] rounded-lg object-cover ring-2 ring-amber-200">
        @else
            <div class="flex size-[17mm] items-center justify-center rounded-lg bg-slate-800 text-sm font-bold text-amber-300">{{ $student->initials() }}</div>
        @endif
        <div class="flex min-w-0 flex-1 flex-col justify-between">
            <div>
                <h1 class="truncate text-[12px] font-bold leading-tight text-slate-900">{{ $student->name }}</h1>
                <p class="font-mono text-[10px] font-semibold text-amber-700">{{ $student->student_code }}</p>
                <p class="mt-0.5 text-[7px] font-semibold uppercase tracking-widest text-slate-500">{{ $student->typeLabel() }}</p>
            </div>
            @include('students.id-cards._details')
        </div>
    </div>
    <div class="h-1 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600"></div>
</article>
