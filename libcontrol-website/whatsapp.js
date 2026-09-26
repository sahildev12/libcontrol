(function () {
    function digits(phone) {
        return String(phone || '').replace(/\D+/g, '');
    }

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    function buildWhatsAppUrl(phone, text) {
        var number = digits(phone);
        if (number === '') {
            return '';
        }

        var encodedText = text ? encodeURIComponent(text) : '';

        if (isMobileDevice()) {
            return 'https://wa.me/' + number + (encodedText ? '?text=' + encodedText : '');
        }

        return 'https://web.whatsapp.com/send?phone=' + number + (encodedText ? '&text=' + encodedText : '');
    }

    function parseWaMeHref(href) {
        if (! href) {
            return null;
        }

        var match = href.match(/wa\.me\/(\d+)(?:\?text=([^&]+))?/i);
        if (! match) {
            return null;
        }

        return {
            phone: match[1],
            text: match[2] ? decodeURIComponent(match[2].replace(/\+/g, ' ')) : '',
        };
    }

    function resolveLink(link) {
        var phone = link.getAttribute('data-wa-phone');
        var text = link.getAttribute('data-wa-text') || '';

        if (phone) {
            return { phone: phone, text: text };
        }

        return parseWaMeHref(link.getAttribute('href') || '');
    }

    function applyWhatsAppLinks() {
        document.querySelectorAll('a[href*="wa.me/"], a[data-wa-phone]').forEach(function (link) {
            var details = resolveLink(link);
            if (! details) {
                return;
            }

            var url = buildWhatsAppUrl(details.phone, details.text);
            if (url !== '') {
                link.setAttribute('href', url);
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href*="wa.me/"], a[data-wa-phone], a[href*="web.whatsapp.com/send"]');
        if (! link) {
            return;
        }

        var details = resolveLink(link);
        if (! details && link.href.indexOf('web.whatsapp.com/send') !== -1) {
            return;
        }

        if (! details) {
            return;
        }

        event.preventDefault();
        window.open(buildWhatsAppUrl(details.phone, details.text), '_blank', 'noopener,noreferrer');
    });

    applyWhatsAppLinks();
})();
