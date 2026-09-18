@props(['name' => 'peace', 'class' => 'h-6 w-6'])

@php
    $classes = $class;
@endphp

@switch($name)
    @case('wifi')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.111 16.404a5.5 5.5 0 017.778 0M5.282 13.213a9 9 0 0113.436 0M2.454 10.022a13 13 0 0119.092 0M12 20h.01"/></svg>
        @break
    @case('secure')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        @break
    @case('seat')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 10v10m14-10v10M7 10h10M9 6h6v4H9V6z"/></svg>
        @break
    @case('ac')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v18m6-15l-6 6-6-6m12 12l-6-6-6 6"/></svg>
        @break
    @case('washroom')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1v4H7v-4zm9 0h1v4h-1v-4zM5 6h14v4H5V6z"/></svg>
        @break
    @case('water')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3c3 4 6 6.5 6 10a6 6 0 11-12 0c0-3.5 3-6 6-10z"/></svg>
        @break
    @case('cctv')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 8h8a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4a2 2 0 012-2z"/></svg>
        @break
    @case('power')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        @break
    @case('silence')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9l4 4m0-4l-4 4"/></svg>
        @break
    @case('food')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M18 8h1a4 4 0 010 8h-1M6 8H5a4 4 0 000 8h1M6 8v8m6-8v8M9 4v4m6-4v4"/></svg>
        @break
    @case('clean')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7"/></svg>
        @break
    @case('respect')
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        @break
    @default
        <svg {{ $attributes->merge(['class' => $classes]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/></svg>
@endswitch
