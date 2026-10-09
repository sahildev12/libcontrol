@props([
    'customUrl' => null,
    'custom' => false,
])

@php
    use App\Support\LibControlBrand;

    $adaptive = LibControlBrand::faviconAdaptiveUrls();
@endphp

@if ($custom && $customUrl)
    <link rel="icon" href="{{ $customUrl }}">
@else
    <link id="lc-favicon" rel="icon" type="image/png" href="{{ $adaptive['light'] }}">
    <script>
        (function () {
            var light = @json($adaptive['light']);
            var dark = @json($adaptive['dark']);

            function applyFavicon() {
                var href = window.matchMedia('(prefers-color-scheme: dark)').matches ? dark : light;
                var link = document.getElementById('lc-favicon');

                if (! link) {
                    link = document.createElement('link');
                    link.id = 'lc-favicon';
                    link.rel = 'icon';
                    link.type = 'image/png';
                    document.head.appendChild(link);
                }

                if (link.getAttribute('href') !== href) {
                    link.setAttribute('href', href);
                }
            }

            applyFavicon();

            if (typeof window.matchMedia === 'function') {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyFavicon);
            }
        })();
    </script>
@endif
