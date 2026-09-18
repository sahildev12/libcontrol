<article class="id-card relative overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200" style="width: 86mm; height: 54mm;">
    <div class="flex h-full">
        <aside class="flex w-[24mm] flex-col items-center justify-between bg-[#4f46e5] px-1.5 py-2 text-white">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="h-[7mm] max-w-[20mm] object-contain brightness-0 invert">
            @else
                <p class="text-center text-[7px] font-bold uppercase tracking-[0.14em] leading-tight">{{ $branchName }}</p>
            @endif

            <div class="overflow-hidden rounded-md ring-2 ring-white/35">
                @include('students.id-cards._photo', [
                    'class' => 'size-[15mm] object-cover',
                    'fallbackClass' => 'flex size-[15mm] items-center justify-center bg-indigo-500 text-xs font-bold text-white',
                ])
            </div>

            <p class="text-[6px] font-semibold uppercase tracking-[0.18em] text-indigo-100">{{ $student->typeLabel() }}</p>
        </aside>

        <section class="relative flex min-w-0 flex-1 flex-col justify-between px-3 py-2">
            <div class="pointer-events-none absolute -right-6 top-4 size-20 rounded-full bg-indigo-50/80"></div>
            <div class="pointer-events-none absolute right-8 top-10 size-10 rounded-full bg-violet-50"></div>

            <header>
                <p class="text-[7px] font-semibold uppercase tracking-[0.2em] text-indigo-600">Student identity card</p>
                <h1 class="mt-0.5 truncate text-[13px] font-bold leading-tight text-slate-900">{{ $student->name }}</h1>
                <p class="font-mono text-[10px] font-semibold text-indigo-700">{{ $student->student_code }}</p>
            </header>

            @include('students.id-cards._meta-grid')

            <footer class="space-y-1">
                <svg viewBox="0 0 180 16" class="h-[4mm] w-full" aria-hidden="true">
                    @foreach (range(0, 35) as $index)
                        <rect x="{{ $index * 5 }}" y="0" width="{{ $index % 3 === 0 ? 3 : 1.5 }}" height="16" fill="{{ $index % 2 === 0 ? '#312e81' : '#c7d2fe' }}"/>
                    @endforeach
                </svg>
                <p class="text-[6px] text-slate-400">{{ $branchName }} · Present at library entry</p>
            </footer>
        </section>
    </div>
</article>
