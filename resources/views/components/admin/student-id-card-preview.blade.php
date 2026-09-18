@props([
    'template' => 'classic',
    'logoUrl' => null,
    'sample' => null,
])

@php
    $sample = $sample ?? config('libcontrol.id_card_preview_sample', []);
    $logoUrl = $logoUrl ?: asset('brand/only-logo-main-color.png');
    $previewView = 'components.admin.id-card-previews.'.$template;

    if (! view()->exists($previewView)) {
        $previewView = 'components.admin.id-card-previews.'.(array_key_exists($template, config('libcontrol.id_card_templates', [])) ? $template : 'classic');
    }

    if (! view()->exists($previewView)) {
        $previewView = 'components.admin.id-card-previews.classic';
    }
@endphp

<div {{ $attributes->merge(['class' => 'id-card-preview mt-4 w-full']) }}>
    @include($previewView, [
        'sample' => $sample,
        'logoUrl' => $logoUrl,
    ])
</div>
