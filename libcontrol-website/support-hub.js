(function () {
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  var STOP_WORDS = (
    'a an and are as at be by can could do does doing done for from get got have how i if in into is it its ' +
    'me my of on or our please should so some that the their them then there these this to us use using want ' +
    'was we what when where which who why will with would you your not no dont cant cannot isnt doesnt wont unable ' +
    'option way possible'
  ).split(' ').reduce(function (set, word) { set[word] = true; return set; }, {});

  var SYNONYM_GROUPS = [
    ['add', 'create', 'new', 'register', 'registration', 'enrol', 'enroll', 'admission', 'admit', 'join', 'make', 'insert'],
    ['student', 'member', 'learner', 'candidate', 'reader', 'customer'],
    ['delete', 'remove', 'erase', 'drop'],
    ['edit', 'update', 'change', 'modify', 'correct'],
    ['fee', 'payment', 'pay', 'paid', 'collect', 'collection', 'money', 'due', 'dues', 'charge', 'amount'],
    ['renew', 'renewal', 'extend', 'extension'],
    ['seat', 'desk', 'chair', 'place', 'spot'],
    ['assign', 'allot', 'allocate', 'allocation', 'book', 'booking', 'give'],
    ['transfer', 'move', 'shift', 'swap'],
    ['release', 'cancel', 'vacate', 'free', 'end', 'left', 'leave'],
    ['login', 'log', 'signin', 'sign'],
    ['password', 'pin', 'passcode'],
    ['forgot', 'forget', 'reset', 'lost', 'recover'],
    ['enquiry', 'inquiry', 'enquire', 'inquire', 'lead', 'prospect', 'walkin'],
    ['expense', 'spend', 'spending', 'cost', 'bill', 'rent', 'salary'],
    ['report', 'statement', 'summary', 'profit', 'loss', 'income', 'earning'],
    ['import', 'upload', 'excel', 'csv', 'xlsx', 'bulk', 'spreadsheet', 'sheet'],
    ['card', 'idcard', 'identity'],
    ['email', 'mail', 'smtp', 'notification'],
    ['branch', 'location', 'centre', 'center', 'franchise'],
    ['hall', 'room', 'floor'],
    ['trial', 'demo', 'try'],
    ['referral', 'refer', 'referred', 'reward'],
    ['attendance', 'checkin', 'present', 'absent'],
    ['website', 'site', 'webpage'],
    ['ticket', 'support', 'help', 'issue', 'problem', 'bug', 'complaint', 'error'],
    ['promotion', 'promote', 'offer', 'campaign', 'marketing', 'announcement'],
    ['installment', 'instalment', 'emi', 'part'],
    ['partial', 'half', 'balance', 'remaining', 'advance'],
    ['increase', 'more', 'extra', 'expand'],
    ['self', 'himself', 'herself', 'themselves', 'own'],
    ['print', 'download', 'export'],
    ['manager', 'staff', 'employee', 'receptionist'],
    ['time', 'timing', 'hour', 'slot', 'schedule'],
  ];

  function stem(word) {
    if (word.length > 4 && /ies$/.test(word)) return word.slice(0, -3) + 'y';
    if (word.length > 5 && /ing$/.test(word)) return word.slice(0, -3);
    if (word.length > 4 && /ed$/.test(word)) return word.slice(0, -2);
    if (word.length > 3 && /s$/.test(word) && !/ss$/.test(word)) return word.slice(0, -1);
    return word;
  }

  var CANONICAL = {};
  SYNONYM_GROUPS.forEach(function (group) {
    group.forEach(function (word) { CANONICAL[stem(word)] = group[0]; });
  });

  function tokenize(text) {
    return String(text || '')
      .toLowerCase()
      .replace(/[’']/g, '')
      .split(/[^a-z0-9]+/)
      .filter(Boolean)
      .map(stem);
  }

  function queryTerms(text) {
    var seen = {};
    return tokenize(text).filter(function (word) {
      if (STOP_WORDS[word] || seen[word]) return false;
      seen[word] = true;
      return true;
    });
  }

  function phraseKey(text) {
    return queryTerms(text).map(function (word) { return CANONICAL[word] || word; }).join(' ');
  }

  function buildIndex(title, otherText, phrases) {
    var index = { title: {}, body: {}, words: [], phrases: [title].concat(phrases || []).map(phraseKey) };
    tokenize(title).forEach(function (word) {
      index.title[word] = true;
      index.title[CANONICAL[word] || word] = true;
    });
    tokenize(title + ' ' + otherText).forEach(function (word) {
      if (!index.body[word]) index.words.push(word);
      index.body[word] = true;
      index.body[CANONICAL[word] || word] = true;
    });
    return index;
  }

  function termMatches(term, map, words) {
    if (map[term] || map[CANONICAL[term] || term]) return true;
    if (term.length < 3 || !words) return false;
    for (var i = 0; i < words.length; i++) {
      if (words[i].indexOf(term) === 0) return true;
    }
    return false;
  }

  function scoreIndex(index, terms, relaxed) {
    if (!terms.length) return 1;
    var matched = 0;
    var score = 0;
    terms.forEach(function (term) {
      if (termMatches(term, index.body, index.words)) {
        matched += 1;
        score += 2;
        if (termMatches(term, index.title)) score += 3;
      }
    });
    var key = terms.map(function (word) { return CANONICAL[word] || word; }).join(' ');
    var phraseHit = index.phrases.some(function (phrase) {
      return phrase.indexOf(key) !== -1 || (phrase.indexOf(' ') !== -1 && key.indexOf(phrase) !== -1);
    });
    if (terms.length > 1 && phraseHit) {
      score += 6;
    }
    var needed = relaxed ? 1 : (terms.length <= 2 ? terms.length : Math.ceil(terms.length * 0.6));
    return matched >= needed ? score + matched : 0;
  }

  function rankItems(items, terms, indexOf) {
    function rank(relaxed) {
      return items
        .map(function (item, order) { return { item: item, score: scoreIndex(indexOf(item), terms, relaxed), order: order }; })
        .filter(function (row) { return row.score > 0; })
        .sort(function (a, b) { return terms.length ? (b.score - a.score) || (a.order - b.order) : a.order - b.order; });
    }
    var rows = rank(false);
    if (!rows.length && terms.length) rows = rank(true);
    if (!terms.length || !rows.length) return rows;
    var cutoff = rows[0].score * 0.5;
    return rows.filter(function (row) { return row.score >= cutoff; });
  }

  function initSupportArticles() {
    var data = window.LIBCONTROL_SUPPORT_ARTICLES;
    var listEl = document.getElementById('articleList');
    var searchEl = document.getElementById('articleSearch');
    var filtersEl = document.getElementById('articleFilters');
    var emptyEl = document.getElementById('articleEmpty');
    var countEl = document.getElementById('articleCount');
    if (!data || !listEl) {
      return;
    }

    var activeCategory = 'all';
    var openId = null;

    function categoryLabel(id) {
      var match = data.categories.find(function (cat) { return cat.id === id; });
      return match ? match.label : id;
    }

    data.articles.forEach(function (article) {
      article._index = buildIndex(article.title, [
        article.summary,
        categoryLabel(article.category),
        (article.keywords || []).join(' '),
        (article.body || []).join(' '),
      ].join(' '), article.keywords);
    });

    function filteredArticles() {
      var terms = queryTerms(searchEl ? searchEl.value : '');
      var inCategory = data.articles.filter(function (article) {
        return activeCategory === 'all' || article.category === activeCategory;
      });
      return rankItems(inCategory, terms, function (article) { return article._index; })
        .map(function (row) { return row.item; });
    }

    function renderFilters() {
      if (!filtersEl) {
        return;
      }
      filtersEl.innerHTML = data.categories.map(function (cat) {
        var active = cat.id === activeCategory;
        return (
          '<button type="button" class="hub-filter' + (active ? ' is-active' : '') + '" data-category="' + escapeHtml(cat.id) + '">' +
            escapeHtml(cat.label) +
          '</button>'
        );
      }).join('');
      filtersEl.querySelectorAll('[data-category]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          activeCategory = btn.getAttribute('data-category') || 'all';
          openId = null;
          renderFilters();
          renderList();
        });
      });
    }

    function renderList() {
      var articles = filteredArticles();
      if (countEl) {
        countEl.textContent = articles.length + ' article' + (articles.length === 1 ? '' : 's');
      }
      if (emptyEl) {
        emptyEl.hidden = articles.length > 0;
      }
      listEl.innerHTML = articles.map(function (article) {
        var isOpen = openId === article.id;
        var body = (article.body || []).map(function (para) {
          return '<p>' + escapeHtml(para) + '</p>';
        }).join('');
        return (
          '<article class="hub-article' + (isOpen ? ' is-open' : '') + '" id="article-' + escapeHtml(article.id) + '">' +
            '<button type="button" class="hub-article-toggle" data-article="' + escapeHtml(article.id) + '" aria-expanded="' + (isOpen ? 'true' : 'false') + '">' +
              '<span class="hub-article-meta">' +
                '<span class="hub-pill">' + escapeHtml(categoryLabel(article.category)) + '</span>' +
                '<h2>' + escapeHtml(article.title) + '</h2>' +
                '<p>' + escapeHtml(article.summary) + '</p>' +
              '</span>' +
              '<i class="fa-solid fa-chevron-down hub-article-chevron" aria-hidden="true"></i>' +
            '</button>' +
            '<div class="hub-article-body" ' + (isOpen ? '' : 'hidden') + '>' + body + '</div>' +
          '</article>'
        );
      }).join('');

      listEl.querySelectorAll('[data-article]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.getAttribute('data-article');
          openId = openId === id ? null : id;
          renderList();
        });
      });
    }

    if (searchEl) {
      searchEl.addEventListener('input', function () {
        var matches = queryTerms(searchEl.value).length ? filteredArticles() : [];
        openId = matches.length ? matches[0].id : null;
        renderList();
      });
    }

    var hash = window.location.hash.replace(/^#/, '');
    if (hash) {
      openId = hash;
    }

    renderFilters();
    renderList();

    if (hash) {
      var target = document.getElementById('article-' + hash);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  }

  function initDocumentation() {
    var data = window.LIBCONTROL_DOCUMENTATION;
    var navEl = document.getElementById('docNav');
    var contentEl = document.getElementById('docContent');
    var searchEl = document.getElementById('docSearch');
    if (!data || !navEl || !contentEl) {
      return;
    }

    data.sections.forEach(function (section) {
      section.topics.forEach(function (topic) {
        topic._index = buildIndex(topic.title, [
          section.title,
          section.summary,
          (topic.keywords || []).join(' '),
          topic.steps.join(' '),
        ].join(' '), topic.keywords);
        topic._section = section;
      });
    });
    var allTopics = data.sections.reduce(function (list, section) { return list.concat(section.topics); }, []);

    function sectionHtml(section) {
      var topics = section.matchedTopics.map(function (topic) {
        var steps = topic.steps.map(function (step, index) {
          return '<li><span class="doc-step-num">' + (index + 1) + '</span><span>' + escapeHtml(step) + '</span></li>';
        }).join('');
        return (
          '<article class="doc-topic" id="' + escapeHtml(topic.id) + '">' +
            '<h3>' + escapeHtml(topic.title) + '</h3>' +
            '<ol class="doc-steps">' + steps + '</ol>' +
          '</article>'
        );
      }).join('');

      return (
        '<section class="doc-section" id="section-' + escapeHtml(section.id) + '" data-section="' + escapeHtml(section.id) + '">' +
          '<div class="doc-section-head">' +
            '<div class="doc-section-icon"><i class="fa-solid ' + escapeHtml(section.icon) + '" aria-hidden="true"></i></div>' +
            '<div>' +
              '<h2>' + escapeHtml(section.title) + '</h2>' +
              '<p>' + escapeHtml(section.summary) + '</p>' +
            '</div>' +
          '</div>' +
          '<div class="doc-topics">' + topics + '</div>' +
        '</section>'
      );
    }

    function emptyHtml() {
      return (
        '<div class="hub-empty">' +
          '<p><strong>No guide matched your question yet.</strong></p>' +
          '<p>Try fewer or simpler words (for example “add student”, “renew fee”, “transfer seat”), browse the ' +
          '<a href="support-articles.html">Support Articles</a>, or ask us on ' +
          '<a href="https://wa.me/918076105181" target="_blank" rel="noopener">WhatsApp</a> / ' +
          '<a href="mailto:info@phenomit.com">info@phenomit.com</a>.</p>' +
        '</div>'
      );
    }

    function render() {
      var terms = queryTerms(searchEl ? searchEl.value : '');
      var sections = [];
      data.sections.forEach(function (section) { section.matchedTopics = []; });
      rankItems(allTopics, terms, function (topic) { return topic._index; }).forEach(function (row) {
        var section = row.item._section;
        if (!section.matchedTopics.length) sections.push(section);
        section.matchedTopics.push(row.item);
      });

      navEl.innerHTML = sections.map(function (section) {
        return (
          '<a class="doc-nav-link" href="#section-' + escapeHtml(section.id) + '">' +
            '<i class="fa-solid ' + escapeHtml(section.icon) + '" aria-hidden="true"></i>' +
            '<span>' + escapeHtml(section.title) + '</span>' +
          '</a>'
        );
      }).join('');

      contentEl.innerHTML = sections.length
        ? sections.map(sectionHtml).join('')
        : emptyHtml();
    }

    if (searchEl) {
      searchEl.addEventListener('input', render);
    }

    render();
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSupportArticles();
    initDocumentation();
  });
})();
