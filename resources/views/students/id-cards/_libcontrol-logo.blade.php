@php
    use App\Support\LibControlBrand;

    $align = $align ?? 'left';
    $logoSrc = $logoUrl ?: LibControlBrand::darkWideLogoWithTextUrl();
    $wrapClass = $align === 'right' ? 'ml-auto items-end text-right' : 'items-start text-left';
@endphp

<div class="flex max-w-[48mm] flex-col {{ $wrapClass }}">
    <img
        src="{{ $logoSrc }}"
        alt="LibControl"
        class="h-[7.5mm] max-w-full object-contain {{ $align === 'right' ? 'object-right' : 'object-left' }}"
    >
</div>
