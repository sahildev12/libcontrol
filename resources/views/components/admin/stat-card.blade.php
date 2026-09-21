@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'brand',
])

<x-admin.kpi-card
    {{ $attributes }}
    :label="$label"
    :value="is_numeric($value) ? number_format($value) : $value"
    :hint="$hint"
/>
