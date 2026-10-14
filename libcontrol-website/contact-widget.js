(function () {
    var WA_PHONE = '918076105181';
    var WA_TEXT = 'Hi, I want to know about LibControl.';
    var BOT_SRC = '/assets/edwik-bot.png';

    function buildWhatsAppHref() {
        var mobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        var text = encodeURIComponent(WA_TEXT);

        if (mobile) {
            return 'https://wa.me/' + WA_PHONE + '?text=' + text;
        }

        return 'https://web.whatsapp.com/send?phone=' + WA_PHONE + '&text=' + text;
    }

    function removeLegacyFloat() {
        document.querySelectorAll('a.wa-float').forEach(function (link) {
            link.remove();
        });
    }

    function mount() {
        if (document.getElementById('lcContactDock')) {
            return;
        }

        removeLegacyFloat();

        var dock = document.createElement('div');
        dock.id = 'lcContactDock';
        dock.className = 'lc-contact-dock';
        dock.setAttribute('role', 'region');
        dock.setAttribute('aria-label', 'Contact LibControl');
        dock.innerHTML =
            '<div class="lc-contact-dock__promo">' +
            '  <img src="' + BOT_SRC + '" width="76" height="76" alt="" class="lc-contact-dock__avatar" decoding="async">' +
            '  <p class="lc-contact-dock__bubble">I can help you grow your library <span aria-hidden="true">✨</span></p>' +
            '</div>' +
            '<a class="lc-wa-trigger" href="' + buildWhatsAppHref() + '" target="_blank" rel="noopener noreferrer">' +
            '  <i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>Chat Now</span>' +
            '</a>';

        document.body.appendChild(dock);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
