(function () {
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');

  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
    });
    links.querySelectorAll('a, .nav-demo-btn').forEach(function (el) {
      el.addEventListener('click', function () {
        links.classList.remove('open');
      });
    });
  }

  var guideNav = document.querySelector('.guide-nav');
  if (guideNav) {
    var setNavHeight = function () {
      document.documentElement.style.setProperty('--nav-height', guideNav.offsetHeight + 'px');
    };
    setNavHeight();
    window.addEventListener('resize', setNavHeight, { passive: true });
    var onNavScroll = function () {
      guideNav.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    onNavScroll();
    window.addEventListener('scroll', onNavScroll, { passive: true });
  }

  function initModuleShowcase() {
    var data = window.LIBCONTROL_MODULES;
    var tabsEl = document.getElementById('moduleTabs');
    var copyEl = document.getElementById('moduleShowcaseCopy');
    var featuresEl = document.getElementById('moduleShowcaseFeatures');
    var bodyEl = document.getElementById('moduleShowcaseBody');
    if (!data || !tabsEl || !copyEl || !featuresEl || !bodyEl) return;

    var activeId = data.order[0];
    var tabButtons = [];

    function escapeHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function renderCopy(mod) {
      var caps = mod.capabilities.map(function (cap) {
        return '<li><span class="module-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>' + escapeHtml(cap) + '</li>';
      }).join('');
      return (
        '<div class="module-showcase-hero">' +
          '<div class="module-showcase-icon-lg"><i class="fa-solid ' + escapeHtml(mod.icon) + '" aria-hidden="true"></i></div>' +
          '<h3 id="modulePanelTitle">' + escapeHtml(mod.title) + '</h3>' +
        '</div>' +
        '<p class="module-showcase-desc">' + escapeHtml(mod.description) + '</p>' +
        '<hr class="module-showcase-rule">' +
        '<p class="module-showcase-subhead">What you can do</p>' +
        '<ul class="module-cap-list">' + caps + '</ul>' +
        '<div class="module-showcase-highlight">' +
          '<i class="fa-solid fa-lightbulb" aria-hidden="true"></i>' +
          '<div>' +
            '<strong>' + escapeHtml(mod.highlightTitle) + '</strong>' +
            '<p>' + escapeHtml(mod.highlightText) + '</p>' +
          '</div>' +
        '</div>'
      );
    }

    function renderFeatures(mod) {
      var blocks = (mod.features || []).map(function (feat) {
        return (
          '<article class="module-feature-block">' +
            '<div class="module-feature-icon"><i class="fa-solid ' + escapeHtml(feat.icon) + '" aria-hidden="true"></i></div>' +
            '<div>' +
              '<h4>' + escapeHtml(feat.title) + '</h4>' +
              '<p>' + escapeHtml(feat.text) + '</p>' +
            '</div>' +
          '</article>'
        );
      }).join('');
      return (
        '<p class="module-showcase-subhead module-showcase-subhead-right">What you get</p>' +
        '<div class="module-feature-list">' + blocks + '</div>'
      );
    }

    function setActive(moduleId, focusTab) {
      if (!data.items[moduleId]) return;
      activeId = moduleId;
      var mod = data.items[moduleId];

      tabButtons.forEach(function (btn) {
        var isActive = btn.getAttribute('data-module') === moduleId;
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        btn.tabIndex = isActive ? 0 : -1;
      });

      bodyEl.classList.add('is-fading');
      window.setTimeout(function () {
        copyEl.innerHTML = renderCopy(mod);
        featuresEl.innerHTML = renderFeatures(mod);
        bodyEl.classList.remove('is-fading');
        if (focusTab) {
          var activeBtn = tabsEl.querySelector('.module-tab.is-active');
          if (activeBtn) activeBtn.focus();
        }
      }, 120);
    }

    tabsEl.innerHTML = '';
    data.order.forEach(function (id, index) {
      var mod = data.items[id];
      if (!mod) return;
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'module-tab' + (index === 0 ? ' is-active' : '');
      btn.setAttribute('role', 'tab');
      btn.setAttribute('data-module', id);
      btn.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
      btn.setAttribute('aria-controls', 'moduleShowcase');
      btn.id = 'module-tab-' + id;
      btn.tabIndex = index === 0 ? 0 : -1;
      btn.innerHTML =
        '<span class="module-tab-inner">' +
          '<span class="module-tab-icon"><i class="fa-solid ' + mod.icon + '" aria-hidden="true"></i></span>' +
          '<span class="module-tab-label">' + escapeHtml(mod.tabLabel) + '</span>' +
        '</span>' +
        '<span class="module-tab-pointer" aria-hidden="true"></span>';
      btn.addEventListener('click', function () { setActive(id); });
      btn.addEventListener('keydown', function (e) {
        var currentIndex = data.order.indexOf(activeId);
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
          e.preventDefault();
          var dir = e.key === 'ArrowRight' ? 1 : -1;
          var next = (currentIndex + dir + data.order.length) % data.order.length;
          setActive(data.order[next], true);
        }
      });
      tabsEl.appendChild(btn);
      tabButtons.push(btn);
    });

    setActive(activeId);
  }

  document.querySelectorAll('.feat-card, .panel-intro, .story-card, .section-head, .table-wrap, .support-card, .plan-card, .pain-card, .pain-intro, .module-tabs-wrap, .callout').forEach(function (el, i) {
    el.classList.add('reveal');
    el.style.setProperty('--reveal-delay', ((i % 6) * 0.07) + 's');
  });

  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.18 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in-view'); });
  }

  var lightbox = document.getElementById('imgLightbox');
  var lightboxImg = document.getElementById('imgLightboxPhoto');
  var lightboxClose = document.getElementById('imgLightboxClose');

  function openLightbox(src, alt) {
    if (!lightbox || !lightboxImg) return;
    lightboxImg.removeAttribute('src');
    lightboxImg.alt = alt || '';
    lightbox.hidden = false;
    lightbox.classList.add('is-loading');
    document.body.style.overflow = 'hidden';
    var loader = new Image();
    loader.onload = function () {
      lightboxImg.src = src;
      lightbox.classList.remove('is-loading');
    };
    loader.onerror = function () {
      lightbox.classList.remove('is-loading');
      lightboxImg.src = src;
    };
    loader.src = src;
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.hidden = true;
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.feat-shot, .section-shot').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var img = btn.querySelector('img');
      var src = btn.getAttribute('data-full') || (img && img.src);
      if (src) openLightbox(src, btn.getAttribute('data-alt') || (img ? img.alt : ''));
    });
  });

  if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });
  }

  var demoModal = document.getElementById('demoModal');
  var demoOpeners = document.querySelectorAll('.open-demo-form, #openDemoForm');
  var demoClose = document.getElementById('demoModalClose');
  var demoDone = document.getElementById('demoFormDone');
  var demoForm = document.getElementById('demoForm');
  var demoSuccess = document.getElementById('demoFormSuccess');
  var demoError = document.getElementById('demoFormError');

  function openDemoModal() {
    if (!demoModal) return;
    if (demoForm) demoForm.hidden = false;
    if (demoSuccess) demoSuccess.hidden = true;
    if (demoError) {
      demoError.hidden = true;
      demoError.textContent = '';
    }
    clearDemoErrors();
    demoModal.hidden = false;
    document.body.style.overflow = 'hidden';
    var first = demoModal.querySelector('input');
    if (first) first.focus();
  }

  function closeDemoModal() {
    if (!demoModal) return;
    demoModal.hidden = true;
    if (!lightbox || lightbox.hidden) document.body.style.overflow = '';
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeLightbox();
      closeDemoModal();
    }
  });

  demoOpeners.forEach(function (btn) {
    btn.addEventListener('click', openDemoModal);
  });
  if (demoClose) demoClose.addEventListener('click', closeDemoModal);
  if (demoDone) demoDone.addEventListener('click', closeDemoModal);
  if (demoModal) {
    demoModal.addEventListener('click', function (e) {
      if (e.target === demoModal) closeDemoModal();
    });
  }

  var demoValidators = {
    name: function (v) {
      v = v.trim().replace(/\s+/g, ' ');
      if (!v) return 'Please enter your name.';
      if (v.length < 2) return 'Name must be at least 2 characters.';
      if (v.length > 80) return 'Name must be 80 characters or fewer.';
      if (!/^[\p{L}][\p{L}\s.'-]*$/u.test(v)) return 'Name can only contain letters, spaces, dots, apostrophes and hyphens.';
      return '';
    },
    phone: function (v) {
      v = v.replace(/\D/g, '');
      if (!v) return 'Please enter your WhatsApp / phone number.';
      if (v.length !== 10) return 'Enter a 10-digit mobile number.';
      if (!/^[6-9]\d{9}$/.test(v)) return 'Enter a valid mobile number.';
      return '';
    },
    institute: function (v) {
      v = v.trim().replace(/\s+/g, ' ');
      if (!v) return 'Please enter your library / institute name.';
      if (v.length < 3) return 'Library name must be at least 3 characters.';
      if (v.length > 120) return 'Library name must be 120 characters or fewer.';
      if (!/^[\p{L}][\p{L}\s&.,'()-]*$/u.test(v)) return 'Library name can only contain letters, spaces and & . , \' ( ) -';
      return '';
    },
    city: function (v) {
      v = v.trim().replace(/\s+/g, ' ');
      if (!v) return '';
      if (v.length < 2) return 'City must be at least 2 characters.';
      if (v.length > 60) return 'City must be 60 characters or fewer.';
      if (!/^[\p{L}][\p{L}\s.'-]*$/u.test(v)) return 'City can only contain letters, spaces, dots and hyphens.';
      return '';
    },
    message: function (v) {
      if (v.trim().length > 1000) return 'Message must be 1000 characters or fewer.';
      return '';
    },
    accept_legal: function (v, el) {
      return el.checked ? '' : 'Please accept the Disclaimer, Terms and Privacy Policy.';
    }
  };

  function demoFieldWrap(key) {
    return demoForm ? demoForm.querySelector('[data-demo-field="' + key + '"]') : null;
  }

  function demoFieldInput(key) {
    var wrap = demoFieldWrap(key);
    return wrap ? wrap.querySelector('input, textarea') : null;
  }

  function setDemoFieldError(key, message) {
    var wrap = demoFieldWrap(key);
    if (!wrap) return;
    var slot = wrap.querySelector('[data-demo-error]');
    var input = demoFieldInput(key);
    wrap.classList.toggle('has-error', !!message);
    if (slot) slot.textContent = message || '';
    if (input) input.setAttribute('aria-invalid', message ? 'true' : 'false');
  }

  function validateDemoField(key) {
    var input = demoFieldInput(key);
    if (!input || !demoValidators[key]) return true;
    var message = demoValidators[key](input.value || '', input);
    setDemoFieldError(key, message);
    return !message;
  }

  function clearDemoErrors() {
    Object.keys(demoValidators).forEach(function (key) { setDemoFieldError(key, ''); });
  }

  if (demoForm) {
    var demoPhoneInput = document.getElementById('demoPhone');
    if (demoPhoneInput) {
      demoPhoneInput.addEventListener('input', function () {
        var digits = demoPhoneInput.value.replace(/\D/g, '');
        if (digits.length > 10 && digits.indexOf('91') === 0) digits = digits.slice(2);
        if (digits.length > 10 && digits.charAt(0) === '0') digits = digits.slice(1);
        if (demoPhoneInput.value !== digits.slice(0, 10)) demoPhoneInput.value = digits.slice(0, 10);
      });
    }

    var lettersOnlyFields = {
      demoName: /[^\p{L}\s.'-]/gu,
      demoCity: /[^\p{L}\s.'-]/gu,
      demoInstitute: /[^\p{L}\s&.,'()-]/gu
    };
    Object.keys(lettersOnlyFields).forEach(function (id) {
      var input = document.getElementById(id);
      var disallowed = lettersOnlyFields[id];
      if (!input) return;
      var sanitize = function (text) {
        return text.replace(disallowed, '').replace(/^[^\p{L}]+/u, '');
      };
      input.addEventListener('input', function () {
        var value = input.value;
        var clean = sanitize(value);
        if (clean === value) return;
        var caret = input.selectionStart || 0;
        var cleanBefore = sanitize(value.slice(0, caret));
        input.value = clean;
        input.setSelectionRange(cleanBefore.length, cleanBefore.length);
      });
    });

    Object.keys(demoValidators).forEach(function (key) {
      var input = demoFieldInput(key);
      if (!input) return;
      var isCheckbox = input.type === 'checkbox';
      input.addEventListener(isCheckbox ? 'change' : 'blur', function () { validateDemoField(key); });
      if (!isCheckbox) {
        input.addEventListener('input', function () {
          if (demoFieldWrap(key).classList.contains('has-error')) validateDemoField(key);
        });
      }
    });

    demoForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var submitBtn = document.getElementById('demoFormSubmit');

      var firstInvalid = null;
      Object.keys(demoValidators).forEach(function (key) {
        if (!validateDemoField(key) && !firstInvalid) firstInvalid = demoFieldInput(key);
      });
      if (firstInvalid) {
        if (demoError) {
          demoError.textContent = 'Please fix the highlighted fields.';
          demoError.hidden = false;
        }
        firstInvalid.focus();
        return;
      }

      var name = document.getElementById('demoName').value.trim().replace(/\s+/g, ' ');
      var phone = document.getElementById('demoPhone').value.replace(/\D/g, '');
      var institute = document.getElementById('demoInstitute').value.trim().replace(/\s+/g, ' ');
      var city = document.getElementById('demoCity').value.trim().replace(/\s+/g, ' ');
      var message = document.getElementById('demoMessage').value.trim();

      if (demoError) {
        demoError.hidden = true;
        demoError.textContent = '';
      }
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
      }

      fetch(new URL('send-demo.php', window.location.href).href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ name: name, phone: phone, institute: institute, city: city, message: message })
      })
        .then(function (res) {
          return res.text().then(function (text) {
            var data = null;
            try { data = text ? JSON.parse(text) : null; } catch (err) { data = null; }
            if (!data) {
              return {
                ok: false,
                message: res.status >= 500
                  ? 'Server error while sending email. Please try again.'
                  : 'Mail service is not available. Open via XAMPP: http://localhost/libcontrol/phenomit.com/libcontrol/'
              };
            }
            return { ok: res.ok && data.ok, message: data.message || '' };
          });
        })
        .then(function (result) {
          if (!result.ok) {
            if (demoError) {
              demoError.textContent = result.message || 'Could not send request. Please try again.';
              demoError.hidden = false;
            }
            return;
          }
          demoForm.reset();
          demoForm.hidden = true;
          if (demoSuccess) demoSuccess.hidden = false;
          if (demoError) demoError.hidden = true;
        })
        .catch(function () {
          if (demoError) {
            demoError.textContent = 'Network error. Open the page via XAMPP (localhost), then try again.';
            demoError.hidden = false;
          }
        })
        .finally(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Pre-Register';
          }
        });
    });
  }

  var backTop = document.getElementById('backToTop');
  if (backTop) {
    function toggleBackTop() {
      backTop.classList.toggle('show', window.scrollY > 320);
    }
    window.addEventListener('scroll', toggleBackTop, { passive: true });
    toggleBackTop();
    backTop.addEventListener('click', function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  initModuleShowcase();

  function initPricingToggle() {
    var toggle = document.getElementById('billingToggle');
    var monthlyLabel = document.getElementById('billingMonthly');
    var yearlyLabel = document.getElementById('billingYearly');
    if (!toggle) return;

    var amounts = document.querySelectorAll('.plan-amount[data-monthly]');
    var wasPrices = document.querySelectorAll('.plan-price-was[data-monthly]');
    var billingLabels = document.querySelectorAll('.plan-billing[data-monthly-label]');
    var equivLines = document.querySelectorAll('.plan-equiv-yearly');
    var isYearly = false;

    function applyBilling() {
      toggle.setAttribute('aria-checked', isYearly ? 'true' : 'false');
      if (monthlyLabel) monthlyLabel.classList.toggle('is-active', !isYearly);
      if (yearlyLabel) yearlyLabel.classList.toggle('is-active', isYearly);

      amounts.forEach(function (el) {
        el.textContent = isYearly ? el.getAttribute('data-yearly') : el.getAttribute('data-monthly');
      });
      wasPrices.forEach(function (el) {
        el.textContent = isYearly ? el.getAttribute('data-yearly') : el.getAttribute('data-monthly');
      });
      billingLabels.forEach(function (el) {
        el.textContent = isYearly ? el.getAttribute('data-yearly-label') : el.getAttribute('data-monthly-label');
      });
      equivLines.forEach(function (el) {
        el.hidden = !isYearly;
      });
    }

    toggle.addEventListener('click', function () {
      isYearly = !isYearly;
      applyBilling();
    });

    applyBilling();
  }

  initPricingToggle();
})();
