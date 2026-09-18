@props(['class' => 'size-10'])

<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 40 40', 'aria-hidden' => 'true']) }}>
    <rect width="40" height="40" fill="#fff" stroke="#cbd5e1" stroke-width="0.5"/>
    <rect x="2" y="2" width="10" height="10" fill="#0f172a"/>
    <rect x="4" y="4" width="6" height="6" fill="#fff"/>
    <rect x="28" y="2" width="10" height="10" fill="#0f172a"/>
    <rect x="30" y="4" width="6" height="6" fill="#fff"/>
    <rect x="2" y="28" width="10" height="10" fill="#0f172a"/>
    <rect x="4" y="30" width="6" height="6" fill="#fff"/>
    @foreach ([14,18,22,26,30] as $x)
        @foreach ([6,10,14,18,22,26,30] as $y)
            @if (($x + $y) % 3 === 0)
                <rect x="{{ $x }}" y="{{ $y }}" width="2" height="2" fill="#0f172a"/>
            @endif
        @endforeach
    @endforeach
</svg>
