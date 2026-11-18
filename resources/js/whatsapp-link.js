// Mobile: wa.me opens the phone app. Desktop: try WhatsApp Desktop (whatsapp://), then WhatsApp Web.
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

function tryDesktopApp(appUrl) {
    return new Promise((resolve) => {
        let handedOff = false;
        const done = (value) => {
            if (handedOff) {
                return;
            }
            handedOff = true;
            cleanup();
            resolve(value);
        };

        const mark = () => done(true);
        window.addEventListener('blur', mark, { once: true });
        const onVis = () => {
            if (document.hidden) {
                mark();
            }
        };
        document.addEventListener('visibilitychange', onVis, { once: true });

        const iframe = document.createElement('iframe');
        iframe.setAttribute('aria-hidden', 'true');
        iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;visibility:hidden';
        iframe.src = appUrl;
        document.body.appendChild(iframe);

        const cleanup = () => {
            window.removeEventListener('blur', mark);
            document.removeEventListener('visibilitychange', onVis);
            iframe.remove();
        };

        window.setTimeout(() => done(false), 1200);
    });
}

function openWebChat(webUrl, tab = null) {
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

/**
 * @param {string} href wa.me or web.whatsapp.com URL
 * @param {Window|null} tab optional tab from a sync click (e.g. Growth checkout)
 */
export async function openWhatsApp(href, tab = null) {
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
    const appUrl = `whatsapp://send?${params}`;

    if (isMobile()) {
        if (tab) {
            tab.location.href = href;
        } else {
            window.location.assign(href);
        }
        return;
    }

    const appOpened = await tryDesktopApp(appUrl);
    if (appOpened) {
        tab?.close();
        return;
    }

    openWebChat(webUrl, tab);
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
    openWhatsApp(href);
});