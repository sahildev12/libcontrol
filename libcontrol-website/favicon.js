(function () {
    var light = '/assets/favicon-light-32x32.png';
    var dark = '/assets/favicon-dark-32x32.png';

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
