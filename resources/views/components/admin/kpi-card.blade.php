@props([
    'label',
    'value',
    'hint' => null,
    'delta' => null,
    'deltaTone' => 'up',
    'badge' => null,
    'compact' => false,
])

<div {{ $attributes->class([
    'lc-kpi-card',
    'lc-kpi-card--compact' => $compact,
    'lc-kpi-card--no-icon' => ! isset($icon),
]) }}>
    <div class="lc-kpi-card__body">
        <div class="lc-kpi-card__label-row">
            <p class="lc-kpi-card__label">{{ $label }}</p>
            @if ($badge)
                <span class="lc-kpi-card__badge">{{ $badge }}</span>
            @endif
        </div>
        <p class="lc-kpi-card__value">{{ $value }}</p>
        @if ($hint)
            <p class="lc-kpi-card__hint">{{ $hint }}</p>
        @endif
        @if ($delta)
            <p @class([
                'lc-kpi-card__delta',
                'lc-kpi-card__delta--up' => $deltaTone === 'up',
                'lc-kpi-card__delta--down' => $deltaTone === 'down',
                'lc-kpi-card__delta--accent' => $deltaTone === 'accent',
            ])>{{ $delta }}</p>
        @endif
    </div>

    @isset($icon)
        <div class="lc-kpi-card__icon">{{ $icon }}</div>
    @endisset
</div>
