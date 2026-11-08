<div class="modal fade" id="photoModal" tabindex="-1" aria-label="Photo viewer" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0">
                <div class="h6 mb-0 text-truncate" id="pmTitle"></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-2"><img id="pmImg" src="" alt="" class="img-fluid" style="max-height:80vh"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var token = document.querySelector('meta[name="csrf-token"]').content;
  var headers = {'X-CSRF-TOKEN': token, 'Accept': 'application/json'};
  var modalEl = document.getElementById('photoModal');
  var img = document.getElementById('pmImg');

  function countView(type, id, from) {
    fetch('/engage/' + type + '/' + id + '/view', {method: 'POST', headers: headers})
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d || !from) { return; }
        var card = from.closest('.item-card');
        var n = card ? card.querySelector('.views-n') : null;
        if (n) { n.textContent = Number(d.views).toLocaleString(); }
      })
      .catch(function () {});
  }

  document.addEventListener('click', function (e) {
    var p = e.target.closest('.js-photo');
    if (p) {
      img.src = p.dataset.full;
      img.alt = p.dataset.title || '';
      document.getElementById('pmTitle').textContent = p.dataset.title || '';
      window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
      countView(p.dataset.type, p.dataset.id, p);
      return;
    }
    var v = e.target.closest('.js-embed');
    if (v) {
      var frame = document.createElement('iframe');
      var src = v.dataset.embed;
      frame.className = 'embed-frame';
      frame.src = src + (src.indexOf('?') > -1 ? '&' : '?') + 'autoplay=1';
      frame.title = 'Video player';
      frame.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen');
      frame.setAttribute('allowfullscreen', '');
      countView(v.dataset.type, v.dataset.id, v);
      v.replaceWith(frame);
    }
  });

  document.addEventListener('play', function (e) {
    var v = e.target;
    if (v.tagName === 'VIDEO' && v.dataset.id && !v.dataset.counted) {
      v.dataset.counted = '1';
      countView(v.dataset.type, v.dataset.id, v);
    }
  }, true);

  modalEl.addEventListener('hidden.bs.modal', function () { img.src = ''; });
});
</script>
