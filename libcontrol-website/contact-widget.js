(function () {
    var WA_PHONE = '918076105181';
    var WA_TEXT = 'Hi, I want to know about LibControl.';
    var BOT_SRC = '/assets/edwik-bot.svg';

    function el(tag, className, html) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (html !== undefined) {
            node.innerHTML = html;
        }

        return node;
    }

    function buildWhatsAppHref() {
        var mobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        var text = encodeURIComponent(WA_TEXT);

        if (mobile) {
            return 'https://wa.me/' + WA_PHONE + '?text=' + text;
        }

        return 'https://web.whatsapp.com/send?phone=' + WA_PHONE + '&text=' + text;
    }

    function openDemoModal() {
        var opener = document.querySelector('.open-demo-form, #openDemoForm');
        if (opener) {
            opener.click();
            return true;
        }

        return false;
    }

    function scrollToHash(id) {
        var target = document.getElementById(id);
        if (! target) {
            if (window.location.pathname === '/' || window.location.pathname.endsWith('index.html')) {
                return false;
            }
            window.location.href = '/#' + id;

            return true;
        }

        target.scrollIntoView({ behavior: 'smooth', block: 'start' });

        return true;
    }

    function removeLegacyFloat() {
        document.querySelectorAll('a.wa-float').forEach(function (link) {
            link.remove();
        });
    }

    function appendMessage(list, text, from) {
        var row = el('div', 'lc-edwik-msg lc-edwik-msg--' + from);
        row.textContent = text;
        list.appendChild(row);
        list.scrollTop = list.scrollHeight;
    }

    function bindEdwikChat(panel, toggle) {
        var list = panel.querySelector('.lc-edwik-panel__messages');
        var form = panel.querySelector('.lc-edwik-panel__form');
        var input = panel.querySelector('.lc-edwik-panel__input');
        var closeBtn = panel.querySelector('.lc-edwik-panel__close');
        var greeted = false;

        function greet() {
            if (greeted) {
                return;
            }
            greeted = true;
            appendMessage(list, 'Hi! I’m Edwik, your LibControl guide. Ask about pricing, demos, or growing your library.', 'bot');
        }

        function reply(userText) {
            var q = String(userText || '').toLowerCase();

            if (q.indexOf('price') !== -1 || q.indexOf('plan') !== -1 || q.indexOf('cost') !== -1) {
                if (! scrollToHash('pricing')) {
                    appendMessage(list, 'See libcontrol.in/#pricing for plans, or pre-register and we’ll call you.', 'bot');
                } else {
                    appendMessage(list, 'Here are our plans — scroll up to compare seats and branches.', 'bot');
                }

                return;
            }

            if (q.indexOf('demo') !== -1 || q.indexOf('trial') !== -1 || q.indexOf('register') !== -1) {
                if (openDemoModal()) {
                    appendMessage(list, 'Opening the pre-register form for you.', 'bot');
                } else {
                    appendMessage(list, 'Email info@katsindia.com or use Chat Now on WhatsApp to book a demo.', 'bot');
                }

                return;
            }

            if (q.indexOf('grow') !== -1 || q.indexOf('marketing') !== -1) {
                appendMessage(list, 'LibControl’s Grow My Library helps with visibility, reviews, and campaigns. Owners see it inside the app after signup.', 'bot');

                return;
            }

            appendMessage(list, 'I can help with pricing, demos, and growth. Try a quick button below, or tap Chat Now to talk to our team on WhatsApp.', 'bot');
        }

        function setOpen(open) {
            panel.hidden = ! open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                greet();
                input.focus();
            }
        }

        toggle.addEventListener('click', function () {
            setOpen(panel.hidden);
        });

        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });

        panel.querySelectorAll('[data-edwik-quick]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-edwik-quick') || '';
                appendMessage(list, text, 'user');
                reply(text);
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var text = input.value.trim();
            if (text === '') {
                return;
            }
            appendMessage(list, text, 'user');
            input.value = '';
            reply(text);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && ! panel.hidden) {
                setOpen(false);
            }
        });
    }

    function mount() {
        if (document.getElementById('lcContactDock')) {
            return;
        }

        removeLegacyFloat();

        var dock = el('div', 'lc-contact-dock');
        dock.id = 'lcContactDock';
        dock.setAttribute('role', 'region');
        dock.setAttribute('aria-label', 'Contact LibControl');

        dock.innerHTML =
            '<div class="lc-edwik-panel" id="lcEdwikPanel" hidden>' +
            '  <div class="lc-edwik-panel__head">' +
            '    <img src="' + BOT_SRC + '" width="40" height="40" alt="" class="lc-edwik-panel__head-icon" decoding="async">' +
            '    <div><strong>Edwik</strong><span>LibControl assistant</span></div>' +
            '    <button type="button" class="lc-edwik-panel__close" aria-label="Close chat">&times;</button>' +
            '  </div>' +
            '  <div class="lc-edwik-panel__messages" role="log" aria-live="polite" aria-relevant="additions"></div>' +
            '  <div class="lc-edwik-panel__quick">' +
            '    <button type="button" data-edwik-quick="What are your pricing plans?">Pricing</button>' +
            '    <button type="button" data-edwik-quick="I want a demo">Book demo</button>' +
            '    <button type="button" data-edwik-quick="How can I grow my library?">Growth tips</button>' +
            '  </div>' +
            '  <form class="lc-edwik-panel__form">' +
            '    <input type="text" class="lc-edwik-panel__input" maxlength="240" placeholder="Ask Edwik…" autocomplete="off">' +
            '    <button type="submit" class="lc-edwik-panel__send" aria-label="Send"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>' +
            '  </form>' +
            '</div>' +
            '<div class="lc-contact-dock__promo">' +
            '  <img src="' + BOT_SRC + '" width="76" height="76" alt="" class="lc-contact-dock__avatar" decoding="async">' +
            '  <p class="lc-contact-dock__bubble">I can help you grow your library <span aria-hidden="true">✨</span></p>' +
            '</div>' +
            '<button type="button" class="lc-edwik-trigger" id="lcEdwikToggle" aria-expanded="false" aria-controls="lcEdwikPanel">' +
            '  <img src="' + BOT_SRC + '" width="28" height="28" alt="" decoding="async">' +
            '  <span>Ask Edwik</span>' +
            '</button>' +
            '<a class="lc-wa-trigger" href="' + buildWhatsAppHref() + '" target="_blank" rel="noopener noreferrer">' +
            '  <i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>Chat Now</span>' +
            '</a>';

        document.body.appendChild(dock);

        bindEdwikChat(
            dock.querySelector('#lcEdwikPanel'),
            dock.querySelector('#lcEdwikToggle')
        );
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
