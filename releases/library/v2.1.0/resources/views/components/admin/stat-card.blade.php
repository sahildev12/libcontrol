@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'blue',
])

@php
    $tones = [
        'blue' => ['border' => 'border-blue-200', 'bg' => 'bg-blue-50', 'label' => 'text-blue-700', 'value' => 'text-blue-900'],
        'green' => ['border' => 'border-emerald-200', 'bg' => 'bg-emerald-50', 'label' => 'text-emerald-700', 'value' => 'text-emerald-900'],
        'slate' => ['border' => 'border-slate-200', 'bg' => 'bg-slate-50', 'label' => 'text-slate-700', 'value' => 'text-slate-900'],
        'cyan' => ['border' => 'border-cyan-200', 'bg' => 'bg-cyan-50', 'label' => 'text-cyan-700', 'value' => 'text-cyan-900'],
        'amber' => ['border' => 'border-amber-200', 'bg' => 'bg-amber-50', 'label' => 'text-amber-700', 'value' => 'text-amber-900'],
        'red' => ['border' => 'border-red-200', 'bg' => 'bg-red-50', 'label' => 'text-red-700', 'value' => 'text-red-900'],
        'purple' => ['border' => 'border-purple-200', 'bg' => 'bg-purple-50', 'label' => 'text-purple-700', 'value' => 'text-purple-900'],
        'pink' => ['border' => 'border-pink-200', 'bg' => 'bg-pink-50', 'label' => 'text-pink-700', 'value' => 'text-pink-900'],
    ];
    $colors = $tones[$tone] ?? $tones['blue'];
@endphp

<div class="rounded-xl border {{ $colors['border'] }} {{ $colors['bg'] }} p-5 shadow-sm">
    <p class="text-sm font-semibold {{ $colors['label'] }}">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold {{ $colors['value'] }}">{{ number_format($value) }}</p>
    @if ($hint)
        <p class="mt-1 text-xs {{ $colors['label'] }} opacity-80">{{ $hint }}</p>
    @endif
</div>
