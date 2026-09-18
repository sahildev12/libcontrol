<article class="id-card relative overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200" style="width: 86mm; height: 54mm;">
    <div class="absolute inset-x-0 top-0 h-[10mm] bg-gradient-to-r from-sky-600 to-cyan-500">
        <svg viewBox="0 0 320 24" class="absolute bottom-0 w-full text-white" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,24 L0,8 Q80,20 160,8 T320,8 L320,24 Z" fill="currentColor"/>
        </svg>
    </div>

    <div class="relative flex h-full flex-col px-3 pb-2 pt-[11mm]">
        <div class="flex min-h-0 flex-1 gap-2.5">
            <div class="w-[18mm] shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 shadow-sm">
                @include('students.id-cards._photo', [
                    'class' => 'size-[18mm] w-full object-cover',
                    'fallbackClass' => 'flex size-[18mm] w-full items-center justify-center bg-sky-100 text-xs font-bold text-sky-800',
                ])
            </div>

            <div class="flex min-w-0 flex-1 flex-col justify-between">
                <div>
                    <p class="text-[6px] font-semibold uppercase tracking-[0.22em] text-sky-700">Official library member</p>
                    <h1 class="mt-0.5 truncate text-[12px] font-bold leading-tight text-slate-900">{{ $student->name }}</h1>
                    <p class="font-mono text-[9px] font-semibold text-slate-600">{{ $student->student_code }}</p>
                </div>

                @include('students.id-cards._meta-grid')
            </div>

            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="h-[8mm] max-w-[16mm] shrink-0 self-start object-contain opacity-90">
            @endif
        </div>

        <footer class="mt-1 flex items-end justify-between gap-2 border-t border-slate-100 pt-1">
            <p class="text-[6px] leading-tight text-slate-500">{{ $branchName }}<br>Authorized for library access only</p>
            @include('components.admin.id-card-previews._qr', ['class' => 'size-[10mm]'])
        </footer>
    </div>
</article>
