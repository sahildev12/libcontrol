@props(['tone' => 'gray'])

@php
    $tones = [
        'gray' => 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-300',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100',
        'red' => 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
    ];
    $classes = $tones[$tone] ?? $tones['gray'];
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => "inline-flex size-8 items-center justify-center rounded-lg border transition-colors {$classes}"]) }}>
    {{ $slot }}
</button>
