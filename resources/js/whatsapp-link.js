/**
 * Mobile: same-tab wa.me → phone app (target=_blank breaks app handoff).
 * Desktop: whatsapp:// (EXE) first; if that fails, open WhatsApp Web.
 * Never open about:blank — that caused the empty tab next to "Open WhatsApp?".
 */
const isMobile = () =>
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    || (navigator.maxTouchPoints > 1 && /Mac/.test(navigator.platform || ''));

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

function waMeUrl(params) {
    const phone = new URLSearchParams(params).get('phone') || '';
    const text = new URLSearchParams(params).get('text');
    let url = `https://wa.me/${phone}`;
    if (text) {
        url += `?text=${encodeURIComponent(text)}`;
    }
    return url;
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

        const anchor = document.createElement('a');
        anchor.href = appUrl;
        anchor.rel = 'noopener';
        anchor.style.display = 'none';
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();

        // Long enough for the OS "Open WhatsApp?" prompt + app focus.
        window.setTimeout(() => finish(false), 2500);
    });
}

/**
 * @param {string} href
 * @param {Window|null} tab unused legacy arg (old Growth blank-tab); closed if passed
 */
export async function openWhatsApp(href, tab = null) {
    try {
        tab?.close();
    } catch {
        // ignore
    }

    if (! href) {
        return;
    }

    const params = chatParams(href);
    if (! params) {
        window.location.assign(href);
        return;
    }

    if (isMobile()) {
        window.location.assign(href.includes('wa.me/') ? href : waMeUrl(params));
        return;
    }

    const appUrl = `whatsapp://send?${params}`;
    const webUrl = webChatUrl(params);

    const appOpened = await tryDesktopApp(appUrl);
    if (appOpened) {
        return;
    }

    const opened = window.open(webUrl, '_blank', 'noopener,noreferrer');
    if (! opened) {
        window.location.assign(webUrl);
    } else {
        opened.opener = null;
    }
}

window.openWhatsApp = openWhatsApp;

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-whatsapp], a[href*="wa.me/"]');
    if (! link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) {
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

    if (isMobile()) {
        window.location.assign(href);
        return;
    }

    openWhatsApp(href);
});
