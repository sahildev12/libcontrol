/**
 * Desktop: always open WhatsApp Web (sync, popup-safe).
 * Browsers cannot detect if WhatsApp Desktop is installed; whatsapp:// fails
 * after uninstall while a stale Windows protocol handler remains, and async
 * window.open() is blocked — so we never use whatsapp:// on desktop.
 * Mobile: wa.me hands off to the phone app.
 *
 * Refs: SuperUser / StackOverflow — use web.whatsapp.com or api + app_absent=1.
 */
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
    // Prefer chat UI in the browser; avoid desktop-app handoff prompts.
    params.set('type', 'phone_number');
    params.set('app_absent', '1');

    return params.toString().replace(/\+/g, '%20');
}

function webChatUrl(params) {
    return `https://web.whatsapp.com/send?${params}`;
}

/**
 * @param {string} href wa.me or web.whatsapp.com URL
 * @param {Window|null} tab optional tab opened during the click gesture
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

    if (isMobile()) {
        if (tab) {
            tab.location.href = href;
        } else {
            window.location.assign(href);
        }
        return;
    }

    const webUrl = webChatUrl(params);

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

    if (! chatParams(href)) {
        return;
    }

    event.preventDefault();
    // Open during the user gesture so popup blockers cannot cancel WhatsApp Web.
    const tab = window.open('about:blank', '_blank');
    openWhatsApp(href, tab);
});
