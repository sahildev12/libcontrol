(function () {
    var WA_PHONE = '918076105181';
    var WA_TEXT = 'Hi, I want to know about LibControl.';
    var BOT_SRC = '/assets/edwik-bot.png?v=6';
    var BUBBLE_LINES = [
        'I can help you grow your library ✨',
        'Get more students for your library',
        'Manage seats, fees and renewals easily',
        'See live occupancy across your halls',
        'Book a free LibControl demo today',
    ];
    var HOLD_MS = 30000;
    var TYPE_MS = 42;
    var DELETE_MS = 28;

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

    function startBubbleTypewriter(textEl) {
        var lineIndex = 0;
        var charIndex = 0;
        var deleting = false;
        var holdTimer = null;
        var stopped = false;

        function schedule(fn, ms) {
            return window.setTimeout(fn, ms);
        }

        function clearHold() {
            if (holdTimer !== null) {
                window.clearTimeout(holdTimer);
                holdTimer = null;
            }
        }

        function step() {
            if (stopped) {
                return;
            }

            var line = BUBBLE_LINES[lineIndex];

            if (! deleting) {
                charIndex += 1;
                textEl.textContent = line.slice(0, charIndex);

                if (charIndex >= line.length) {
                    textEl.textContent = line;
                    clearHold();
                    holdTimer = schedule(function () {
                        deleting = true;
                        holdTimer = null;
                        schedule(step, DELETE_MS);
                    }, HOLD_MS);

                    return;
                }

                schedule(step, TYPE_MS);

                return;
            }

            charIndex -= 1;
            textEl.textContent = line.slice(0, charIndex);

            if (charIndex <= 0) {
                deleting = false;
                lineIndex = (lineIndex + 1) % BUBBLE_LINES.length;
                schedule(step, TYPE_MS + 120);

                return;
            }

            schedule(step, DELETE_MS);
        }

        textEl.textContent = '';
        schedule(step, 400);

        return function stop() {
            stopped = true;
            clearHold();
        };
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
            '  <img src="' + BOT_SRC + '" width="64" height="64" alt="Edwik assistant" class="lc-contact-dock__avatar" decoding="async">' +
            '  <p class="lc-contact-dock__bubble" aria-live="polite">' +
            '    <span class="lc-contact-dock__bubble-text" id="lcEdwikBubbleText"></span>' +
            '  </p>' +
            '</div>' +
            '<a class="lc-wa-trigger" href="' + buildWhatsAppHref() + '" target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp">' +
            '  <i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>Chat Now</span>' +
            '</a>';

        document.body.appendChild(dock);

        var textEl = dock.querySelector('#lcEdwikBubbleText');
        if (textEl) {
            startBubbleTypewriter(textEl);
        }
    }

    function syncBrowserBottomInset() {
        var inset = 0;
        if (window.visualViewport && window.matchMedia('(max-width: 768px)').matches) {
            var gap = window.innerHeight - window.visualViewport.height - window.visualViewport.offsetTop;
            if (gap > 0 && gap < 200) {
                inset = Math.round(gap);
            }
        }
        var minInset = window.matchMedia('(max-width: 768px)').matches ? 48 : 0;
        document.documentElement.style.setProperty('--browser-bottom-inset', Math.max(inset, minInset) + 'px');
    }

    function bindViewportInsets() {
        syncBrowserBottomInset();
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', syncBrowserBottomInset);
            window.visualViewport.addEventListener('scroll', syncBrowserBottomInset);
        }
        window.addEventListener('resize', syncBrowserBottomInset);
        window.addEventListener('orientationchange', syncBrowserBottomInset);
    }

    function bindHeroPromoLayout() {
        var hero = document.querySelector('header.hero-growth, header.hero');
        var dock = document.getElementById('lcContactDock');

        if (!hero || !dock) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    dock.classList.toggle('lc-contact-dock--hero-inview', entry.isIntersecting);
                });
            },
            { threshold: 0.12, rootMargin: '-8% 0px 0px 0px' }
        );

        observer.observe(hero);
    }

    /** Tablet only: hide Chat Now + bot until the user reaches the page bottom. */
    function bindTabletBottomOnlyDock() {
        var dock = document.getElementById('lcContactDock');
        if (!dock || !window.matchMedia) {
            return;
        }

        var mq = window.matchMedia('(min-width: 768px) and (max-width: 1199.98px)');
        var BOTTOM_PX = 64;

        function update() {
            if (!mq.matches) {
                dock.classList.remove('lc-contact-dock--await-bottom');
                dock.classList.remove('lc-contact-dock--at-bottom');
                return;
            }

            dock.classList.add('lc-contact-dock--await-bottom');
            var doc = document.documentElement;
            var remaining = doc.scrollHeight - (window.scrollY + window.innerHeight);
            dock.classList.toggle('lc-contact-dock--at-bottom', remaining <= BOTTOM_PX);
        }

        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        if (mq.addEventListener) {
            mq.addEventListener('change', update);
        } else if (mq.addListener) {
            mq.addListener(update);
        }
        update();
    }

    function init() {
        mount();
        bindViewportInsets();
        bindHeroPromoLayout();
        bindTabletBottomOnlyDock();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
