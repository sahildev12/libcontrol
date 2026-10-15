{{-- Card artwork + photo + details. Positions come from config('libcontrol.id_card_layouts') and config('libcontrol.id_card_text'). --}}
@php
    $layout = config('libcontrol.id_card_layouts.'.$layoutKey, config('libcontrol.id_card_layouts.classic'));
    $photo = $layout['photo'];
    $logoBox = $layout['logo'] ?? null;
    $logoAlign = is_array($logoBox) ? ($logoBox['align'] ?? 'left') : 'left';
    $logoPadding = is_array($logoBox) ? (float) ($logoBox['padding'] ?? 0) : 0;
    $text = config('libcontrol.id_card_text');
    $fontSize = $text['font_size'];
    $values = [
        'name' => $name ?? '—',
        'father_name' => $fatherName ?? '—',
        'date_of_birth' => $dateOfBirth ?? '—',
        'student_id' => $studentId ?? '—',
    ];
    $px = fn (float $percent): string => round($percent * 3.25, 2).'px';
    $fitPreview = ! empty($fitPreview);
    $cardWidth = empty($fluid) ? '325px' : '100%';
    $cardHeight = empty($fluid) ? '204px' : '100%';
@endphp
<div
    @class(['id-card-preview__card' => $fitPreview])
    style="position: relative; container-type: inline-size; width: {{ $cardWidth }}; height: {{ $cardHeight }}; overflow: hidden; background: #fff;{{ empty($fluid) && ! $fitPreview ? ' box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);' : '' }}"
>
    <img
        src="{{ $backgroundUrl }}"
        alt=""
        style="position: absolute; inset: 0; z-index: 0; display: block; width: 100%; height: 100%; object-fit: fill; print-color-adjust: exact; -webkit-print-color-adjust: exact;"
    >

    @if (is_array($logoBox) && (! empty($bindLogoFromAlpine) || ! empty($logoUrl)))
        <div
            style="position: absolute; z-index: 2; left: {{ $logoBox['left'] }}%; top: {{ $logoBox['top'] }}%; width: {{ $logoBox['width'] }}%; height: {{ $logoBox['height'] }}%; box-sizing: border-box; padding: {{ $logoPadding }}%; overflow: hidden; display: flex; align-items: center; justify-content: {{ $logoAlign === 'right' ? 'flex-end' : 'flex-start' }}; pointer-events: none; line-height: 0;"
        >
            @if (! empty($bindLogoFromAlpine))
                <img
                    x-show="idCardLogoPreviewUrl || platformSettings.id_card_logo_url"
                    x-cloak
                    :src="idCardLogoPreviewUrl || platformSettings.id_card_logo_url"
                    alt=""
                    style="display: block; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; object-position: {{ $logoAlign === 'right' ? 'right center' : 'left center' }}; print-color-adjust: exact; -webkit-print-color-adjust: exact;"
                >
            @elseif (! empty($logoUrl))
                <img
                    src="{{ $logoUrl }}"
                    alt=""
                    style="display: block; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; object-position: {{ $logoAlign === 'right' ? 'right center' : 'left center' }}; print-color-adjust: exact; -webkit-print-color-adjust: exact;"
                >
            @endif
        </div>
    @endif

    <div
        style="position: absolute; z-index: 2; left: {{ $photo['left'] }}%; top: {{ $photo['top'] }}%; width: {{ $photo['width'] }}%; height: {{ $photo['height'] }}%; overflow: hidden; border-radius: {{ $px($photo['radius']) }}; border-radius: {{ $photo['radius'] }}cqw; background: #fff; line-height: 0;"
    >
        @if (! empty($photoUrl))
            <img
                src="{{ $photoUrl }}"
                alt=""
                style="display: block; width: 100%; height: 100%; object-fit: cover; object-position: center center; print-color-adjust: exact; -webkit-print-color-adjust: exact;"
            >
        @else
            <div style="display: table; width: 100%; height: 100%; background: #f8fafc;">
                <span style="display: table-cell; vertical-align: middle; text-align: center; color: #0c1648; font-size: {{ $px($fontSize) }}; font-size: {{ $fontSize }}cqw; font-weight: 700; font-family: Arial, Helvetica, sans-serif; line-height: 1;">{{ $photoInitials ?? '—' }}</span>
            </div>
        @endif
    </div>

    @foreach ($text['rows'] as $key => $row)
        <div style="position: absolute; z-index: 2; left: {{ $row['left'] }}%; width: {{ round($text['line_end'] - $row['left'], 2) }}%; bottom: {{ round(100 - $row['line'] + $text['gap_above_line'], 2) }}%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; line-height: 1.1; font-family: Arial, Helvetica, sans-serif; font-size: {{ $px($fontSize) }}; font-size: {{ $fontSize }}cqw; font-weight: 700; color: #0c1648; letter-spacing: 0.01em;">{{ $values[$key] }}</div>
    @endforeach
</div>
