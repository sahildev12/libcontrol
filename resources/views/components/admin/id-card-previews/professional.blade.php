<article class="relative w-full overflow-hidden rounded-lg bg-white shadow-md ring-1 ring-slate-200" style="aspect-ratio: 86/54;">
    <div class="absolute inset-x-0 top-0 h-[18%] bg-gradient-to-r from-sky-600 to-cyan-500">
        <svg viewBox="0 0 320 24" class="absolute bottom-0 w-full text-white" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,24 L0,8 Q80,20 160,8 T320,8 L320,24 Z" fill="currentColor"/>
        </svg>
    </div>

    <div class="relative flex h-full flex-col px-3 pb-2 pt-[20%]">
        <div class="flex min-h-0 flex-1 gap-2">
            <div class="w-[20%] shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-md'])
            </div>

            <div class="flex min-w-0 flex-1 flex-col justify-between">
                <div>
                    <p class="text-[5px] font-semibold uppercase tracking-[0.2em] text-sky-700">Official library member</p>
                    <h3 class="mt-0.5 truncate text-[13px] font-bold leading-tight text-slate-900">{{ $sample['name'] }}</h3>
                    <p class="font-mono text-[9px] font-semibold text-slate-600">{{ $sample['student_id'] }}</p>
                </div>

                @include('components.admin.id-card-previews._meta-grid')
            </div>

            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="h-4 max-w-[18%] shrink-0 self-start object-contain opacity-90">
            @endif
        </div>

        <footer class="mt-1 flex items-end justify-between gap-2 border-t border-slate-100 pt-1">
            <p class="text-[5px] leading-tight text-slate-500">{{ $sample['branch'] }}<br>Authorized access only</p>
            @include('components.admin.id-card-previews._qr', ['class' => 'size-9'])
        </footer>
    </div>
</article>
