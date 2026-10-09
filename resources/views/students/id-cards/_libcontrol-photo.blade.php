@php
    $variant = $variant ?? 'classic';
@endphp

<div class="relative shrink-0" style="width: {{ $size ?? '22mm' }}; height: {{ $size ?? '22mm' }};">
    @if ($variant === 'classic')
        <div class="absolute bottom-0 right-0 size-full translate-x-[1.2mm] translate-y-[1.2mm] rounded-[2mm] bg-[#fecf25]"></div>
        <div class="relative z-10 size-full overflow-hidden rounded-[2mm] bg-[#fecf25] p-[1.2mm]">
            <div class="size-full overflow-hidden rounded-[1.5mm] bg-white">
                @include('students.id-cards._photo', [
                    'class' => 'size-full object-cover',
                    'fallbackClass' => 'flex size-full items-center justify-center bg-[#0c1648] text-sm font-bold text-white',
                ])
            </div>
        </div>
    @elseif ($variant === 'modern')
        <div class="absolute bottom-0 right-0 size-full translate-x-[1mm] translate-y-[1mm] rounded-[2mm] bg-[#fecf25]"></div>
        <div class="relative z-10 size-full overflow-hidden rounded-[2mm] border-[0.35mm] border-[#0c1648] bg-white">
            @include('students.id-cards._photo', [
                'class' => 'size-full object-cover',
                'fallbackClass' => 'flex size-full items-center justify-center bg-slate-100 text-sm font-bold text-[#0c1648]',
            ])
        </div>
    @else
        <div class="absolute bottom-0 right-0 size-full translate-x-[1mm] translate-y-[1mm] rounded-[1.5mm] bg-[#fecf25]"></div>
        <div class="relative z-10 size-full overflow-hidden rounded-[1.5mm] border-[0.4mm] border-[#0c1648] bg-white">
            @include('students.id-cards._photo', [
                'class' => 'size-full object-cover',
                'fallbackClass' => 'flex size-full items-center justify-center bg-slate-100 text-sm font-bold text-[#0c1648]',
            ])
        </div>
    @endif
</div>
