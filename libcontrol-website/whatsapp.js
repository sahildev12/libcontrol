(function () {
    function digits(phone) {
        return String(phone || '').replace(/\D+/g, '');
    }

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
            || (navigator.maxTouchPoints > 1 && /Mac/.test(navigator.platform || ''));
    }

    function buildWhatsAppUrl(phone, text) {
        var number = digits(phone);
        if (number === '') {
            return '';
        }

        var encodedText = text ? encodeURIComponent(text) : '';
        return 'https://wa.me/' + number + (encodedText ? '?text=' + encodedText : '');
    }

    function buildWhatsAppWebUrl(phone, text) {
        var number = digits(phone);
        if (number === '') {
            return '';
        }

        var params = 'phone=' + number;
        if (text) {
            params += '&text=' + encodeURIComponent(text);
        }

        return 'https://web.whatsapp.com/send?' + params;
    }

    function buildWhatsAppAppUrl(phone, text) {
        var number = digits(phone);
        if (number === '') {
            return '';
        }

        var params = 'phone=' + number;
        if (text) {
            params += '&text=' + encodeURIComponent(text);
        }

        return 'whatsapp://send?' + params;
    }

    function parseWaMeHref(href) {
        if (!href) {
            return null;
        }

        var match = href.match(/(?:wa\.me\/|api\.whatsapp\.com\/send\?phone=|web\.whatsapp\.com\/send\?phone=)(\d+)(?:(?:\?|&)text=([^&]+))?/i);
        if (!match) {
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

    function tryDesktopApp(appUrl) {
        return new Promise(function (resolve) {
            var handedOff = false;
            function done(value) {
                if (handedOff) {
                    return;
                }
                handedOff = true;
                cleanup();
                resolve(value);
            }

            function mark() {
                done(true);
            }

            window.addEventListener('blur', mark, { once: true });
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    mark();
                }
            }, { once: true });

            var iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;visibility:hidden';
            iframe.src = appUrl;
            document.body.appendChild(iframe);

            function cleanup() {
                window.removeEventListener('blur', mark);
                iframe.parentNode && iframe.parentNode.removeChild(iframe);
            }

            window.setTimeout(function () {
                done(false);
            }, 1200);
        });
    }

    function openWhatsAppChat(phone, text, fallbackUrl) {
        var waUrl = fallbackUrl || buildWhatsAppUrl(phone, text);
        var webUrl = buildWhatsAppWebUrl(phone, text);
        var appUrl = buildWhatsAppAppUrl(phone, text);

        if (isMobileDevice()) {
            window.location.href = waUrl;
            return;
        }

        tryDesktopApp(appUrl).then(function (appOpened) {
            if (appOpened) {
                return;
            }
            var opened = window.open(webUrl, '_blank', 'noopener,noreferrer');
            if (opened) {
                opened.opener = null;
            } else {
                window.location.href = webUrl;
            }
        });
    }

    function applyWhatsAppLinks() {
        document.querySelectorAll('a[href*="wa.me/"], a[href*="web.whatsapp.com/"], a[href*="api.whatsapp.com/"], a[data-wa-phone]').forEach(function (link) {
            var details = resolveLink(link);
            if (!details) {
                return;
            }

            var url = buildWhatsAppUrl(details.phone, details.text);
            if (url === '') {
                return;
            }

            link.setAttribute('href', url);
            link.setAttribute('rel', 'noopener noreferrer');

            if (isMobileDevice()) {
                link.removeAttribute('target');
            } else {
                link.setAttribute('target', '_blank');
            }

            if (link.dataset.waBound === '1') {
                return;
            }
            link.dataset.waBound = '1';

            link.addEventListener('click', function (e) {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                    return;
                }
                e.preventDefault();
                openWhatsAppChat(details.phone, details.text, url);
            });
        });
    }

    window.LibControlApplyWhatsAppLinks = applyWhatsAppLinks;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyWhatsAppLinks);
    } else {
        applyWhatsAppLinks();
    }
})();