(function () {
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
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

    function filteredArticles() {
      var term = (searchEl && searchEl.value ? searchEl.value : '').trim().toLowerCase();
      return data.articles.filter(function (article) {
        if (activeCategory !== 'all' && article.category !== activeCategory) {
          return false;
        }
        if (!term) {
          return true;
        }
        var haystack = [
          article.title,
          article.summary,
          categoryLabel(article.category),
          (article.body || []).join(' '),
        ].join(' ').toLowerCase();
        return haystack.indexOf(term) !== -1;
      });
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
        openId = null;
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

    function sectionHtml(section) {
      var topics = section.topics.map(function (topic) {
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

    function render() {
      var term = (searchEl && searchEl.value ? searchEl.value : '').trim().toLowerCase();
      var sections = data.sections.filter(function (section) {
        if (!term) {
          return true;
        }
        var haystack = [
          section.title,
          section.summary,
          section.topics.map(function (topic) {
            return [topic.title].concat(topic.steps).join(' ');
          }).join(' '),
        ].join(' ').toLowerCase();
        return haystack.indexOf(term) !== -1;
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
        : '<p class="hub-empty">No documentation matched your search.</p>';
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
