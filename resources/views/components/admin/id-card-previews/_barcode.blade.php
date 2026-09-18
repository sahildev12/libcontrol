@props(['class' => 'h-4 w-full', 'light' => false])

<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 120 16', 'preserveAspectRatio' => 'none', 'aria-hidden' => 'true']) }}>
    @php
        $bars = [2,1,3,1,2,2,1,4,1,2,1,3,2,1,2,1,3,1,2,2,1,2,3,1,1,2,2,1,3,1,2];
        $x = 0;
    @endphp
    @foreach ($bars as $width)
        <rect x="{{ $x }}" y="0" width="{{ $width }}" height="16" fill="{{ $light ? '#f8fafc' : '#0f172a' }}"/>
        @php $x += $width + 1; @endphp
    @endforeach
</svg>
