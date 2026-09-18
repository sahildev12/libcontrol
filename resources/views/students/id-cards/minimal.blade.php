<article class="id-card relative w-[86mm] overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200" style="height: 54mm;">
    <div class="absolute inset-y-0 left-0 w-1.5 bg-emerald-500"></div>
    <div class="flex h-full pl-3 pr-3">
        <div class="flex w-[20mm] flex-col items-center justify-center gap-1.5 py-2">
            @if ($student->photoUrl())
                <img src="{{ $student->photoUrl() }}" alt="" class="size-[15mm] rounded-full object-cover ring-2 ring-emerald-100">
            @else
                <div class="flex size-[15mm] items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">{{ $student->initials() }}</div>
            @endif
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="h-[6mm] max-w-[16mm] object-contain opacity-80">
            @endif
        </div>
        <div class="flex flex-1 flex-col justify-between py-2.5 pl-2">
            <div>
                <p class="text-[7px] font-semibold uppercase tracking-[0.2em] text-emerald-600">{{ $branchName }}</p>
                <h1 class="mt-0.5 text-[12px] font-bold leading-tight text-slate-900">{{ $student->name }}</h1>
                <p class="font-mono text-[9px] font-semibold text-slate-600">{{ $student->student_code }}</p>
            </div>
            @include('students.id-cards._details')
            <p class="text-[7px] text-slate-400">{{ $student->typeLabel() }} member</p>
        </div>
    </div>
</article>
