// wa.me shows WhatsApp's "Open app / Continue to WhatsApp Web" page on desktop browsers.
// Links marked data-whatsapp open the installed app directly, falling back to WhatsApp Web.
const isMobile = () => /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

function chatParams(href) {
    const url = new URL(href, window.location.origin);
    const phone = url.pathname.replace(/\D+/g, '');
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
 * Open WhatsApp for a wa.me / web.whatsapp.com URL.
 * On desktop prefers the installed app, then WhatsApp Web.
 * @param {string} href
 * @param {Window|null} tab  optional blank tab opened during a click (avoids popup blockers)
 */
export function openWhatsApp(href, tab = null) {
    if (! href) {
        tab?.close();
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

    const params = chatParams(href);
    if (! params) {
        if (tab) {
            tab.location.href = href;
        } else {
            window.location.assign(href);
        }
        return;
    }

    const appUrl = `whatsapp://send?${params}`;
    const webUrl = `https://web.whatsapp.com/send?${params}`;

    if (tab) {
        tab.location.href = appUrl;
        setTimeout(() => {
            try {
                if (tab.closed) {
                    return;
                }
                // If the protocol handler did not navigate away, fall back to WhatsApp Web.
                tab.location.href = webUrl;
            } catch {
                // Cross-origin after app handoff — treat as success.
            }
        }, 1500);
        return;
    }

    let appOpened = false;
    const markOpened = () => { appOpened = true; };
    window.addEventListener('blur', markOpened, { once: true });
    document.addEventListener('visibilitychange', markOpened, { once: true });

    window.location.href = appUrl;

    setTimeout(() => {
        window.removeEventListener('blur', markOpened);
        document.removeEventListener('visibilitychange', markOpened);
        if (appOpened || ! document.hasFocus()) {
            return;
        }

        const opened = window.open(webUrl, '_blank');
        if (opened) {
            opened.opener = null;
        } else {
            window.location.href = webUrl;
        }
    }, 1500);
}

window.openWhatsApp = openWhatsApp;

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-whatsapp]');
    if (! link || isMobile() || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
    }

    const params = chatParams(link.href);
    if (! params) {
        return;
    }

    event.preventDefault();
    openWhatsApp(link.href);
});
