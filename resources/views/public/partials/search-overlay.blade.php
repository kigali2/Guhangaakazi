<div id="searchOverlay" class="search-overlay d-none" role="dialog" aria-modal="true" aria-label="Search">
    <div class="search-wrap">
        <div class="search-bar">
            <button type="button" class="btn btn-link text-dark fs-4 p-1" id="searchBack" aria-label="Close search"><i class="bi bi-arrow-left"></i></button>
            <input type="search" id="searchInput" class="search-input" placeholder="Search GuhangaAkazi" autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="search" aria-label="Search">
            <button type="button" class="btn btn-link text-dark fs-5 p-1 d-none" id="searchClear" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="search-body"><div id="searchList" role="listbox"></div></div>
        <div id="searchLive" class="visually-hidden" aria-live="polite"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var overlay = document.getElementById('searchOverlay');
  var input = document.getElementById('searchInput');
  var list = document.getElementById('searchList');
  var clearBtn = document.getElementById('searchClear');
  var live = document.getElementById('searchLive');
  var KEY = 'ga_recent';
  var ICONS = {'business-ideas': 'bi-lightbulb', 'skills': 'bi-mortarboard', 'opportunities': 'bi-rocket-takeoff', 'articles': 'bi-journal-text', 'photos': 'bi-image', 'videos': 'bi-play-circle', 'category': 'bi-tag'};
  var timer = null;
  var ctrl = null;
  var active = -1;

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined) { n.textContent = text; }
    return n;
  }
  function recent() {
    try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return []; }
  }
  function saveRecent(q) {
    q = (q || '').trim();
    if (!q) { return; }
    try {
      var r = recent().filter(function (x) { return x.toLowerCase() !== q.toLowerCase(); });
      r.unshift(q);
      localStorage.setItem(KEY, JSON.stringify(r.slice(0, 8)));
    } catch (e) {}
  }
  function goSearch(q) {
    q = (q || '').trim();
    if (!q) { return; }
    saveRecent(q);
    window.location.href = '/search?q=' + encodeURIComponent(q);
  }
  function items() { return list.querySelectorAll('.sr-item'); }
  function setActive(i) {
    var all = items();
    all.forEach(function (n, idx) { n.classList.toggle('active', idx === i); });
    active = i;
    if (all[i]) { all[i].scrollIntoView({block: 'nearest'}); }
  }
  function highlight(node, text, q) {
    var idx = text.toLowerCase().indexOf(q.toLowerCase());
    if (idx < 0) { node.textContent = text; return; }
    node.appendChild(document.createTextNode(text.slice(0, idx)));
    node.appendChild(el('strong', '', text.slice(idx, idx + q.length)));
    node.appendChild(document.createTextNode(text.slice(idx + q.length)));
  }

  function renderRecent() {
    list.textContent = '';
    active = -1;
    var r = recent();
    if (!r.length) {
      list.appendChild(el('p', 'text-muted text-center py-5 px-3 mb-0', 'Search business ideas, skills, opportunities, articles, photos and videos'));
      return;
    }
    r.forEach(function (q) {
      var row = el('div', 'sr-item');
      row.setAttribute('role', 'option');
      row.appendChild(el('i', 'bi bi-clock-history sr-ico'));
      var t = el('div', 'sr-text');
      t.appendChild(el('div', 'sr-title', q));
      row.appendChild(t);
      var fill = el('button', 'btn btn-link text-dark p-1');
      fill.type = 'button';
      fill.setAttribute('aria-label', 'Use ' + q);
      fill.appendChild(el('i', 'bi bi-arrow-up-left fs-5'));
      fill.addEventListener('click', function (e) { e.stopPropagation(); input.value = q; input.focus(); onInput(); });
      row.appendChild(fill);
      row.addEventListener('click', function () { goSearch(q); });
      list.appendChild(row);
    });
    var clear = el('button', 'btn btn-link btn-sm text-muted ms-2 my-2', 'Clear history');
    clear.type = 'button';
    clear.addEventListener('click', function () { try { localStorage.removeItem(KEY); } catch (e) {} renderRecent(); });
    list.appendChild(clear);
  }

  function renderResults(results, q) {
    list.textContent = '';
    active = -1;
    var first = el('div', 'sr-item');
    first.setAttribute('role', 'option');
    first.appendChild(el('i', 'bi bi-search sr-ico'));
    var ft = el('div', 'sr-text');
    ft.appendChild(el('div', 'sr-title', 'Search for "' + q + '"'));
    first.appendChild(ft);
    first.addEventListener('click', function () { goSearch(q); });
    list.appendChild(first);

    if (!results.length) {
      list.appendChild(el('p', 'text-muted text-center py-4 mb-0', 'No matches yet. Try another word.'));
      live.textContent = 'No results';
      return;
    }
    results.forEach(function (r) {
      var a = el('a', 'sr-item');
      a.href = r.url;
      a.setAttribute('role', 'option');
      a.appendChild(el('i', 'bi ' + (ICONS[r.type] || 'bi-search') + ' sr-ico'));
      var t = el('div', 'sr-text');
      var title = el('div', 'sr-title');
      highlight(title, r.title, q);
      t.appendChild(title);
      t.appendChild(el('div', 'sr-sub', r.label + (r.sub ? ' - ' + r.sub : '')));
      a.appendChild(t);
      if (r.image) {
        var im = el('img', 'sr-thumb');
        im.src = r.image;
        im.alt = '';
        im.loading = 'lazy';
        a.appendChild(im);
      }
      a.addEventListener('click', function () { saveRecent(q); });
      list.appendChild(a);
    });
    live.textContent = results.length + ' results';
  }

  function run(q) {
    if (ctrl) { ctrl.abort(); }
    ctrl = new AbortController();
    fetch('/search/suggest?q=' + encodeURIComponent(q), {headers: {'Accept': 'application/json'}, signal: ctrl.signal})
      .then(function (r) { return r.json(); })
      .then(function (d) { if (input.value.trim() === q) { renderResults(d.results, q); } })
      .catch(function (e) { if (e.name !== 'AbortError') { list.textContent = 'Search is unavailable. Please try again.'; } });
  }
  function onInput() {
    var q = input.value.trim();
    clearBtn.classList.toggle('d-none', !q);
    clearTimeout(timer);
    if (!q) {
      if (ctrl) { ctrl.abort(); }
      renderRecent();
      live.textContent = '';
      return;
    }
    timer = setTimeout(function () { run(q); }, 180);
  }

  function open() {
    overlay.classList.remove('d-none');
    document.body.classList.add('overflow-hidden');
    input.value = '';
    clearBtn.classList.add('d-none');
    renderRecent();
    setTimeout(function () { input.focus(); }, 30);
  }
  function close() {
    overlay.classList.add('d-none');
    document.body.classList.remove('overflow-hidden');
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('.js-open-search')) { e.preventDefault(); open(); }
  });
  document.getElementById('searchBack').addEventListener('click', close);
  clearBtn.addEventListener('click', function () { input.value = ''; onInput(); input.focus(); });
  input.addEventListener('input', onInput);
  input.addEventListener('keydown', function (e) {
    var all = items();
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(active + 1, all.length - 1)); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(active - 1, -1)); }
    else if (e.key === 'Enter') {
      e.preventDefault();
      if (active >= 0 && all[active]) { all[active].click(); } else { goSearch(input.value); }
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.classList.contains('d-none')) { close(); }
  });
});
</script>
