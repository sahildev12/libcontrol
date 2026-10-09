@php
    $variant = $variant ?? 'classic';
@endphp

<div class="relative shrink-0" style="width: {{ $size ?? '22%' }}; height: {{ $size ?? '22%' }}; min-width: 3.2rem; min-height: 3.2rem;">
    @if ($variant === 'classic')
        <div class="absolute bottom-0 right-0 size-full translate-x-0.5 translate-y-0.5 rounded bg-[#fecf25]"></div>
        <div class="relative z-10 size-full overflow-hidden rounded bg-[#fecf25] p-0.5">
            <div class="size-full overflow-hidden rounded-sm bg-white">
                @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-sm', 'class' => 'size-full'])
            </div>
        </div>
    @elseif ($variant === 'modern')
        <div class="absolute bottom-0 right-0 size-full translate-x-0.5 translate-y-0.5 rounded bg-[#fecf25]"></div>
        <div class="relative z-10 size-full overflow-hidden rounded border border-[#0c1648] bg-white">
            @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-sm', 'class' => 'size-full'])
        </div>
    @else
        <div class="absolute bottom-0 right-0 size-full translate-x-0.5 translate-y-0.5 rounded-sm bg-[#fecf25]"></div>
        <div class="relative z-10 size-full overflow-hidden rounded-sm border-2 border-[#0c1648] bg-white">
            @include('components.admin.id-card-previews._avatar', ['rounded' => 'rounded-sm', 'class' => 'size-full'])
        </div>
    @endif
</div>
