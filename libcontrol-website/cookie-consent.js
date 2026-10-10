(function () {
  'use strict';

  var CONSENT_COOKIE = 'lc_cookie_consent';
  var CONSENT_DAYS = 180;
  var GA_ID = 'G-EQFF6E1DZ3';
  var PIXEL_ID = '2324179914993901';
  var TRACKING_COOKIE = /^(_ga|_gid|_gat|_gcl_|_fbp|_fbc)/;

  var trackersLoaded = false;
  var banner = null;

  function readConsent() {
    var match = document.cookie.match(new RegExp('(?:^|;\\s*)' + CONSENT_COOKIE + '=(accepted|rejected)'));
    return match ? match[1] : null;
  }

  function saveConsent(value) {
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = CONSENT_COOKIE + '=' + value + '; Max-Age=' + (CONSENT_DAYS * 86400) + '; Path=/; SameSite=Lax' + secure;
  }

  function loadGoogleAnalytics() {
    window['ga-disable-' + GA_ID] = false;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', GA_ID);

    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
    document.head.appendChild(script);
  }

  function loadMetaPixel() {
    if (!window.fbq) {
      var fbq = window.fbq = function () {
        fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
      };
      if (!window._fbq) window._fbq = fbq;
      fbq.push = fbq;
      fbq.loaded = true;
      fbq.version = '2.0';
      fbq.queue = [];

      var script = document.createElement('script');
      script.async = true;
      script.src = 'https://connect.facebook.net/en_US/fbevents.js';
      document.head.appendChild(script);
    }
    window.fbq('consent', 'grant');
    window.fbq('init', PIXEL_ID);
    window.fbq('track', 'PageView');
  }

  function loadTrackers() {
    if (trackersLoaded) return;
    trackersLoaded = true;
    loadGoogleAnalytics();
    loadMetaPixel();
  }

  function stopTrackers() {
    window['ga-disable-' + GA_ID] = true;
    if (typeof window.fbq === 'function') window.fbq('consent', 'revoke');

    var host = window.location.hostname;
    var domains = ['', host, '.' + host, '.' + host.replace(/^www\./, '')];

    document.cookie.split(';').forEach(function (part) {
      var name = part.split('=')[0].trim();
      if (!TRACKING_COOKIE.test(name)) return;
      domains.forEach(function (domain) {
        document.cookie = name + '=; Max-Age=0; Path=/' + (domain ? '; Domain=' + domain : '');
      });
    });
  }

  function hideBanner() {
    if (banner) banner.hidden = true;
  }

  function choose(value) {
    saveConsent(value);
    if (value === 'accepted') {
      loadTrackers();
    } else {
      stopTrackers();
    }
    hideBanner();
  }

  function buildBanner() {
    banner = document.createElement('div');
    banner.className = 'cookie-banner';
    banner.setAttribute('role', 'region');
    banner.setAttribute('aria-label', 'Cookie consent');
    banner.innerHTML =
      '<div class="cookie-banner__text">' +
        '<p class="cookie-banner__title"><i class="fa-solid fa-cookie-bite" aria-hidden="true"></i> We use cookies</p>' +
        '<p>We use essential cookies to run this site. With your permission we also use analytics and marketing cookies ' +
        '(Google Analytics, Meta Pixel) to understand visits and improve LibControl. ' +
        '<a href="/privacy-policy.html#cookies">Learn more</a></p>' +
      '</div>' +
      '<div class="cookie-banner__actions">' +
        '<button type="button" class="cookie-banner__btn cookie-banner__btn--reject" data-cookie-choice="rejected">Reject</button>' +
        '<button type="button" class="cookie-banner__btn cookie-banner__btn--accept" data-cookie-choice="accepted">Accept</button>' +
      '</div>';

    banner.addEventListener('click', function (event) {
      var button = event.target.closest('[data-cookie-choice]');
      if (button) choose(button.getAttribute('data-cookie-choice'));
    });

    document.body.appendChild(banner);
  }

  function showBanner() {
    if (!banner) buildBanner();
    banner.hidden = false;
  }

  function init() {
    var consent = readConsent();

    if (consent === 'accepted') {
      loadTrackers();
    } else if (consent === 'rejected') {
      stopTrackers();
    } else {
      showBanner();
    }
  }

  window.LibControlCookies = {
    accept: function () { choose('accepted'); },
    reject: function () { choose('rejected'); },
    consent: readConsent,
    open: showBanner
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
