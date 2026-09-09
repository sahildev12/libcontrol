@props(['icon' => 'view', 'tone' => 'gray'])

@php
    $tones = [
        'gray' => 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100',
        'red' => 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100',
    ];

    $labels = [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'id-card' => 'ID card',
        'add-fee' => 'Add fee',
        'renew' => 'Renew plan',
    ];

    $classes = 'inline-flex size-8 shrink-0 items-center justify-center rounded-lg border transition-colors '.($tones[$tone] ?? $tones['gray']);
    $label = $labels[$icon] ?? 'Action';
@endphp

<button
    type="button"
    title="{{ $label }}"
    aria-label="{{ $label }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    @switch($icon)
        @case('view')
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            @break
        @case('edit')
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            @break
        @case('delete')
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            @break
        @case('id-card')
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
            @break
        @case('add-fee')
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @break
        @case('renew')
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 8a8 8 0 00-14.9-3M4 16a8 8 0 0014.9 3"/></svg>
            @break
    @endswitch
</button>
