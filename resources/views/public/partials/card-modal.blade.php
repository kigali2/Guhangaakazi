<div class="modal fade" id="cardCommentModal" tabindex="-1" aria-labelledby="cmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 text-truncate" id="cmTitle">Comments</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><div id="cmList"></div></div>
            <div class="modal-footer d-block">
                <div id="cmNote" class="small text-primary mb-2 d-none"></div>
                <div class="row g-2">
                    <div class="col-12"><input type="text" id="cmName" class="form-control" placeholder="Enter your name" maxlength="80" aria-label="Enter your name" autocomplete="name"></div>
                    <div class="col-12"><textarea id="cmBody" rows="2" class="form-control" placeholder="Write a comment" maxlength="1500" aria-label="Comment"></textarea></div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <button type="button" id="cmSend" class="btn btn-primary">Post comment</button>
                    <button type="button" id="cmCancel" class="btn btn-link d-none">Cancel reply</button>
                    <span id="cmMsg" class="small text-danger"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var token = document.querySelector('meta[name="csrf-token"]').content;
  var headers = {'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json'};
  var modalEl = document.getElementById('cardCommentModal');
  var list = document.getElementById('cmList');
  var note = document.getElementById('cmNote');
  var cancel = document.getElementById('cmCancel');
  var msg = document.getElementById('cmMsg');
  var current = null;

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined) { n.textContent = text; }
    return n;
  }

  function clearReply() {
    if (current) { current.parent = null; }
    note.classList.add('d-none');
    cancel.classList.add('d-none');
  }
  cancel.addEventListener('click', clearReply);

  function row(c, isReply) {
    var wrap = el('div', 'd-flex gap-2 ' + (isReply ? 'mt-3' : 'mb-3'));
    wrap.appendChild(el('div', 'avatar' + (isReply ? ' sm' : ''), (c.name || '?').charAt(0).toUpperCase()));
    var body = el('div', 'flex-grow-1');
    var head = el('div');
    head.appendChild(el('strong', '', c.name));
    head.appendChild(el('span', 'text-muted small ms-2', c.when));
    var text = el('div', '', c.body);
    text.style.whiteSpace = 'pre-wrap';
    body.appendChild(head);
    body.appendChild(text);
    if (!isReply) {
      var reply = el('button', 'btn btn-link btn-sm p-0', 'Reply');
      reply.type = 'button';
      reply.addEventListener('click', function () {
        current.parent = c.id;
        note.textContent = 'Replying to ' + c.name;
        note.classList.remove('d-none');
        cancel.classList.remove('d-none');
        document.getElementById('cmBody').focus();
      });
      body.appendChild(reply);
      (c.replies || []).forEach(function (r) { body.appendChild(row(r, true)); });
    }
    wrap.appendChild(body);
    return wrap;
  }

  function load() {
    fetch('/engage/' + current.type + '/' + current.id + '/comments', {headers: {'Accept': 'application/json'}})
      .then(function (r) { return r.json(); })
      .then(function (d) {
        list.textContent = '';
        if (!d.comments.length) { list.appendChild(el('p', 'text-muted', 'No comments yet. Be the first to comment.')); return; }
        d.comments.forEach(function (c) { list.appendChild(row(c, false)); });
      })
      .catch(function () { list.textContent = 'Could not load comments.'; });
  }

  var busy = false;
  document.addEventListener('click', function (e) {
    var like = e.target.closest('.js-like');
    if (like) {
      if (busy) { return; }
      busy = true;
      fetch('/engage/' + like.dataset.type + '/' + like.dataset.id + '/like', {method: 'POST', headers: headers})
        .then(function (r) { return r.json(); })
        .then(function (d) {
          like.classList.toggle('on', !!d.liked);
          like.setAttribute('aria-pressed', d.liked ? 'true' : 'false');
          like.querySelector('i').className = 'bi ' + (d.liked ? 'bi-heart-fill' : 'bi-heart');
          like.querySelector('.n').textContent = Number(d.likes).toLocaleString();
        })
        .catch(function () {})
        .then(function () { busy = false; });
      return;
    }
    var cm = e.target.closest('.js-comments');
    if (cm) {
      current = {type: cm.dataset.type, id: cm.dataset.id, btn: cm, parent: null};
      document.getElementById('cmTitle').textContent = cm.dataset.title || 'Comments';
      msg.textContent = '';
      clearReply();
      list.textContent = 'Loading...';
      window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
      load();
    }
  });

  document.getElementById('cmSend').addEventListener('click', function () {
    if (!current) { return; }
    msg.textContent = '';
    var payload = {
      name: document.getElementById("cmName").value,
      body: document.getElementById('cmBody').value,
      parent_id: current.parent ? parseInt(current.parent, 10) : null
    };
    fetch('/engage/' + current.type + '/' + current.id + '/comment', {method: 'POST', headers: headers, body: JSON.stringify(payload)})
      .then(function (r) {
        if (r.status === 201) {
          return r.json().then(function (d) {
            document.getElementById('cmBody').value = '';
            clearReply();
            current.btn.querySelector('.n').textContent = Number(d.comments).toLocaleString();
            load();
          });
        }
        if (r.status === 429) { msg.textContent = 'Please wait a moment before commenting again.'; return null; }
        return r.json().then(function (j) {
          msg.textContent = j.errors ? Object.values(j.errors)[0][0] : (j.message || 'Could not post your comment.');
        });
      })
      .catch(function () { msg.textContent = 'Could not post your comment. Please try again.'; });
  });
});
</script>
