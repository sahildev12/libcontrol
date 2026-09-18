@props(['class' => 'size-full', 'rounded' => 'rounded-md'])

<div {{ $attributes->merge(['class' => "overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 {$rounded} {$class}"]) }}>
    <svg viewBox="0 0 80 96" class="size-full" aria-hidden="true">
        <rect width="80" height="96" fill="#e2e8f0"/>
        <ellipse cx="40" cy="34" rx="18" ry="20" fill="#94a3b8"/>
        <path d="M12 96c4-18 16-28 28-28s24 10 28 28" fill="#94a3b8"/>
        <ellipse cx="40" cy="34" rx="14" ry="16" fill="#cbd5e1"/>
        <path d="M16 96c3-14 12-22 24-22s21 8 24 22" fill="#cbd5e1"/>
    </svg>
</div>
