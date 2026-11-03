// wa.me shows WhatsApp's "Open app / Continue to WhatsApp Web" page on desktop browsers.
// Links marked data-whatsapp open the installed app directly, falling back to WhatsApp Web.
const isMobile = () => /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

function chatParams(href) {
    const url = new URL(href);
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

    let appOpened = false;
    const markOpened = () => { appOpened = true; };
    window.addEventListener('blur', markOpened, { once: true });
    document.addEventListener('visibilitychange', markOpened, { once: true });

    window.location.href = `whatsapp://send?${params}`;

    setTimeout(() => {
        window.removeEventListener('blur', markOpened);
        document.removeEventListener('visibilitychange', markOpened);
        if (appOpened || ! document.hasFocus()) {
            return;
        }

        const webUrl = `https://web.whatsapp.com/send?${params}`;
        const tab = window.open(webUrl, '_blank');
        if (tab) {
            tab.opener = null;
        } else {
            window.location.href = webUrl;
        }
    }, 1500);
});
