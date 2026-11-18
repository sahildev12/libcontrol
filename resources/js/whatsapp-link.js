// Desktop: open WhatsApp Web directly (avoids broken whatsapp:// handlers after uninstalling the desktop app).
// Mobile: wa.me hands off to the phone app reliably.
const isMobile = () => /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

function chatParams(href) {
    const url = new URL(href, window.location.origin);
    let phone = url.pathname.replace(/\D+/g, '');
    if (! phone && url.hostname.includes('whatsapp')) {
        phone = (url.searchParams.get('phone') || '').replace(/\D+/g, '');
    }
    if (! phone) {
        return null;
    }

    const params = new URLSearchParams({ phone });
    const text = url.searchParams.get('text');
    if (text) {
        params.set('text', text);
    }

    return params.toString().replace(/\+/g, '%20');
}

/**
 * @param {string} href wa.me or web.whatsapp.com URL
 * @param {Window|null} tab optional tab opened synchronously on click (popup-safe)
 */
export function openWhatsApp(href, tab = null) {
    if (! href) {
        tab?.close();
        return;
    }

    const params = chatParams(href);
    if (! params) {
        if (tab) {
            tab.location.href = href;
        } else {
            window.location.assign(href);
        }
        return;
    }

    const webUrl = `https://web.whatsapp.com/send?${params}`;

    if (isMobile()) {
        if (tab) {
            tab.location.href = href;
        } else {
            window.location.assign(href);
        }
        return;
    }

    if (tab) {
        tab.location.href = webUrl;
        tab.opener = null;
        return;
    }

    const opened = window.open(webUrl, '_blank', 'noopener,noreferrer');
    if (opened) {
        opened.opener = null;
    } else {
        window.location.assign(webUrl);
    }
}

window.openWhatsApp = openWhatsApp;

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-whatsapp], a[href*="wa.me/"]');
    if (! link || isMobile() || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) {
        return;
    }

  if (link.hasAttribute('data-wa-skip')) {
        return;
    }

    const href = link.getAttribute('href') || '';
    if (! href.includes('wa.me/') && ! link.hasAttribute('data-whatsapp')) {
        return;
    }

    const params = chatParams(href);
    if (! params) {
        return;
    }

    event.preventDefault();
    const tab = window.open('about:blank', '_blank');
    openWhatsApp(href, tab);
});