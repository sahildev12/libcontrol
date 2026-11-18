/**
 * Mobile: wa.me → phone app.
 * Desktop priority: WhatsApp Desktop (whatsapp://), else WhatsApp Web.
 * Opens a blank tab during the click so web fallback is never popup-blocked.
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

    return params.toString().replace(/\+/g, '%20');
}

function webChatUrl(params) {
    return `https://web.whatsapp.com/send?${params}&type=phone_number&app_absent=1`;
}

function tryDesktopApp(appUrl) {
    return new Promise((resolve) => {
        let settled = false;
        const finish = (opened) => {
            if (settled) {
                return;
            }
            settled = true;
            window.removeEventListener('blur', onBlur);
            document.removeEventListener('visibilitychange', onVis);
            iframe.remove();
            resolve(opened);
        };

        const onBlur = () => finish(true);
        const onVis = () => {
            if (document.hidden) {
                finish(true);
            }
        };

        window.addEventListener('blur', onBlur);
        document.addEventListener('visibilitychange', onVis);

        const iframe = document.createElement('iframe');
        iframe.setAttribute('aria-hidden', 'true');
        iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;visibility:hidden';
        iframe.src = appUrl;
        document.body.appendChild(iframe);

        window.setTimeout(() => finish(false), 1000);
    });
}

/**
 * @param {string} href
 * @param {Window|null} tab blank tab opened in the same click gesture (required for web fallback)
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

    if (isMobile()) {
        if (tab) {
            tab.location.href = href;
        } else {
            window.location.assign(href);
        }
        return;
    }

    const appUrl = `whatsapp://send?${params}`;
    const webUrl = webChatUrl(params);

    const appOpened = await tryDesktopApp(appUrl);
    if (appOpened) {
        tab?.close();
        return;
    }

    if (tab) {
        try {
            tab.location.href = webUrl;
            tab.opener = null;
        } catch {
            window.location.assign(webUrl);
        }
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
    const tab = window.open('about:blank', '_blank');
    openWhatsApp(href, tab);
});
