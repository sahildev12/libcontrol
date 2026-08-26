@props(['tone' => 'gray'])

@php
    $tones = [
        'gray' => 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100',
        'red' => 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100',
    ];
    $classes = $tones[$tone] ?? $tones['gray'];
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => "inline-flex items-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-colors {$classes}"]) }}>
    {{ $slot }}
</button>
