(function () {
    function digits(phone) {
        return String(phone || '').replace(/\D+/g, '');
    }

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
            || (navigator.maxTouchPoints > 1 && /Mac/.test(navigator.platform || ''));
    }

    /**
     * Universal click-to-chat URL.
     * - Mobile: opens WhatsApp app
     * - Desktop with WhatsApp installed: OS/browser can hand off to the app
     * - Otherwise: WhatsApp Web chat
     * Avoid web.whatsapp.com/send — that always forces the browser site.
     */
    function buildWhatsAppUrl(phone, text) {
        var number = digits(phone);
        if (number === '') {
            return '';
        }

        var encodedText = text ? encodeURIComponent(text) : '';
        return 'https://wa.me/' + number + (encodedText ? '?text=' + encodedText : '');
    }

    function buildWhatsAppAppUrl(phone, text) {
        var number = digits(phone);
        if (number === '') {
            return '';
        }

        var encodedText = text ? encodeURIComponent(text) : '';
        return 'whatsapp://send?phone=' + number + (encodedText ? '&text=' + encodedText : '');
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

    function openWhatsAppChat(phone, text, fallbackUrl) {
        var appUrl = buildWhatsAppAppUrl(phone, text);
        var webUrl = fallbackUrl || buildWhatsAppUrl(phone, text);

        if (isMobileDevice() || !appUrl) {
            window.location.href = webUrl;
            return;
        }

        /* Desktop: try native app protocol first, then fall back to wa.me */
        var blurred = false;
        function onBlur() {
            blurred = true;
        }
        window.addEventListener('blur', onBlur);

        var iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;visibility:hidden';
        iframe.src = appUrl;
        document.body.appendChild(iframe);

        window.setTimeout(function () {
            window.removeEventListener('blur', onBlur);
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
            if (!blurred) {
                window.open(webUrl, '_blank', 'noopener,noreferrer');
            }
        }, 900);
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
                /* Same-tab handoff opens the WhatsApp app more reliably */
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
