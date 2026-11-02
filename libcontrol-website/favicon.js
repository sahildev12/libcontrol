(function () {
    var dark = '/assets/favicon-dark-32x32.png';

    function applyFavicon() {
        var link = document.getElementById('lc-favicon');

        if (! link) {
            link = document.createElement('link');
            link.id = 'lc-favicon';
            link.rel = 'icon';
            link.type = 'image/png';
            document.head.appendChild(link);
        }

        if (link.getAttribute('href') !== dark) {
            link.setAttribute('href', dark);
        }
    }

    applyFavicon();
})();
