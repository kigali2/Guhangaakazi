@extends('admin.layouts.app')
@section('title', 'Link media')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Link media to content</h1>
    <a href="{{ route('admin.links.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ route('admin.links.store') }}">
    @csrf
    <div class="card border-0 shadow-sm mb-3"><div class="card-body"><div class="row g-3">
        <div class="col-12 col-md-4">
            <label class="form-label" for="lt">1. Content type</label>
            <select id="lt" name="content_type" class="form-select">
                @foreach ($types as $key => $cfg)<option value="{{ $key }}" @selected(old('content_type') === $key)>{{ $cfg['label'] }}</option>@endforeach
            </select>
        </div>
        <div class="col-12 col-md-8">
            <label class="form-label" for="lc">2. Content item</label>
            <select id="lc" name="content_id" class="form-select"></select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="lr">3. Media role</label>
            <select id="lr" name="role" class="form-select">
                @foreach ($roles as $key => $r)<option value="{{ $key }}" @selected(old('role') === $key)>{{ $r[0] }}</option>@endforeach
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="lcap">Caption (optional)</label>
            <input id="lcap" type="text" name="caption" value="{{ old('caption') }}" class="form-control" maxlength="255">
        </div>
    </div></div></div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <label class="form-label" for="lq">4. Choose files from the Media Library</label>
        <input id="lq" type="search" class="form-control mb-3" placeholder="Search files">
        <div class="row g-2" id="lg"></div>
        <p id="lempty" class="text-muted small d-none mb-0">No matching files. Add files in the Media Library first.</p>
    </div></div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <h2 class="h6">Preview before saving</h2>
        <div id="lp" class="small text-muted">Choose content and files to see a preview.</div>
    </div></div>
    <button class="btn btn-primary btn-lg w-100 mb-4">Save links</button>
</form>

<script>
(function () {
  var D = @json($payload);
  var typeSel = document.getElementById('lt');
  var itemSel = document.getElementById('lc');
  var roleSel = document.getElementById('lr');
  var grid = document.getElementById('lg');
  var search = document.getElementById('lq');
  var empty = document.getElementById('lempty');
  var prev = document.getElementById('lp');

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined) { n.textContent = text; }
    return n;
  }
  function fillItems() {
    itemSel.textContent = '';
    (D.items[typeSel.value] || []).forEach(function (i) {
      var o = el('option', '', i.title);
      o.value = i.id;
      itemSel.appendChild(o);
    });
    preview();
  }
  function selected() {
    return Array.prototype.slice.call(grid.querySelectorAll('input:checked')).map(function (c) { return c.closest('label'); });
  }
  function build() {
    grid.textContent = '';
    D.media.forEach(function (m) {
      var col = el('div', 'col-4 col-md-3 col-lg-2');
      col.dataset.type = m.type;
      col.dataset.name = m.name.toLowerCase();
      var label = el('label', 'media-pick');
      var box = document.createElement('input');
      box.type = 'checkbox';
      box.name = 'media_ids[]';
      box.value = m.id;
      var thumb;
      if (m.type === 'image') { thumb = el('img', 'mp-thumb'); thumb.src = m.url; thumb.alt = ''; thumb.loading = 'lazy'; }
      else { thumb = el('div', 'mp-thumb', m.type === 'document' ? 'PDF' : 'VIDEO'); }
      label.appendChild(box);
      label.appendChild(thumb);
      label.appendChild(el('div', 'small text-truncate px-1', m.name));
      label.dataset.name = m.name;
      label.dataset.type = m.type;
      label.dataset.url = m.url;
      box.addEventListener('change', function () {
        if (roleSel.value === 'featured' && box.checked) {
          grid.querySelectorAll('input:checked').forEach(function (o) { if (o !== box) { o.checked = false; o.closest('label').classList.remove('selected'); } });
        }
        label.classList.toggle('selected', box.checked);
        preview();
      });
      col.appendChild(label);
      grid.appendChild(col);
    });
    filter();
  }
  function filter() {
    var need = D.roleTypes[roleSel.value];
    var q = search.value.trim().toLowerCase();
    var shown = 0;
    grid.querySelectorAll('.col-4').forEach(function (col) {
      var ok = col.dataset.type === need && (q === '' || col.dataset.name.indexOf(q) > -1);
      col.classList.toggle('d-none', !ok);
      if (!ok) {
        var b = col.querySelector('input');
        if (b.checked) { b.checked = false; col.querySelector('label').classList.remove('selected'); }
      } else { shown++; }
    });
    empty.classList.toggle('d-none', shown > 0);
    preview();
  }
  function preview() {
    prev.textContent = '';
    var chosen = selected();
    var item = itemSel.options[itemSel.selectedIndex];
    if (!item || !chosen.length) { prev.textContent = 'Choose content and files to see a preview.'; return; }
    prev.appendChild(el('div', 'mb-2', 'Linking ' + chosen.length + ' file(s) as "' + roleSel.options[roleSel.selectedIndex].text + '" to: ' + item.text));
    var row = el('div', 'd-flex flex-wrap gap-2');
    chosen.forEach(function (l) {
      var t;
      if (l.dataset.type === 'image') { t = el('img', 'rounded'); t.src = l.dataset.url; t.alt = ''; t.style.height = '70px'; }
      else { t = el('div', 'bg-light rounded px-3 py-4 fw-semibold', l.dataset.type === 'document' ? 'PDF' : 'VIDEO'); }
      row.appendChild(t);
    });
    prev.appendChild(row);
  }

  typeSel.addEventListener('change', fillItems);
  itemSel.addEventListener('change', preview);
  roleSel.addEventListener('change', filter);
  search.addEventListener('input', filter);
  fillItems();
  build();
})();
</script>
@endsection
