<section id="comments" class="mt-5" data-base="/engage/{{ $type }}/{{ $item->id }}">
    <div class="d-flex align-items-center flex-wrap gap-3 mb-4">
        <button type="button" id="likeBtn" class="btn {{ $liked ? 'btn-danger' : 'btn-outline-danger' }}" aria-pressed="{{ $liked ? 'true' : 'false' }}">
            <i class="bi {{ $liked ? 'bi-heart-fill' : 'bi-heart' }}"></i> <span id="likeCount">{{ number_format($item->likes_count) }}</span>
        </button>
        <span class="text-muted"><i class="bi bi-eye"></i> <span id="viewCount">{{ number_format($item->views_count) }}</span> views</span>
        <span class="text-muted"><i class="bi bi-chat-dots"></i> {{ number_format($item->comments_count) }} comments</span>
    </div>

    <h2 class="h5">Comments</h2>
    <form id="commentForm" class="card border-0 shadow-sm mb-4" novalidate>
        <div class="card-body">
            <div id="replyNote" class="small text-primary mb-2 d-none"></div>
            <input type="hidden" id="c_parent" value="">
            <div class="row g-2">
                <div class="col-12"><input type="text" id="c_name" class="form-control" placeholder="Enter your name" maxlength="80" aria-label="Enter your name" autocomplete="name" required></div>
                <div class="col-12"><textarea id="c_body" rows="3" class="form-control" placeholder="Write a comment" maxlength="1500" aria-label="Comment"></textarea></div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <button type="submit" class="btn btn-primary">Post comment</button>
                <button type="button" id="cancelReply" class="btn btn-link d-none">Cancel reply</button>
                <span id="commentMsg" class="small text-danger"></span>
            </div>
        </div>
    </form>

    @forelse ($comments as $c)
        <div class="d-flex gap-3 mb-3">
            <div class="avatar">{{ strtoupper(mb_substr($c->name, 0, 1)) }}</div>
            <div class="flex-grow-1">
                <div><strong>{{ $c->name }}</strong> <span class="text-muted small">{{ $c->created_at->diffForHumans() }}</span></div>
                <div>{!! nl2br(e($c->body)) !!}</div>
                <button type="button" class="btn btn-link btn-sm p-0 reply-btn" data-id="{{ $c->id }}" data-name="{{ $c->name }}">Reply</button>
                @foreach ($c->replies as $r)
                    <div class="d-flex gap-3 mt-3">
                        <div class="avatar sm">{{ strtoupper(mb_substr($r->name, 0, 1)) }}</div>
                        <div>
                            <div><strong>{{ $r->name }}</strong> <span class="text-muted small">{{ $r->created_at->diffForHumans() }}</span></div>
                            <div>{!! nl2br(e($r->body)) !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-muted">No comments yet. Be the first to comment.</p>
    @endforelse
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('comments');
  if (!root) { return; }
  var base = root.dataset.base;
  var token = document.querySelector('meta[name="csrf-token"]').content;
  var headers = {'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json'};

  fetch(base + '/view', {method: 'POST', headers: headers})
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (d) { if (d) { document.getElementById('viewCount').textContent = Number(d.views).toLocaleString(); } })
    .catch(function () {});

  var likeBtn = document.getElementById('likeBtn');
  likeBtn.addEventListener('click', function () {
    fetch(base + '/like', {method: 'POST', headers: headers})
      .then(function (r) { return r.json(); })
      .then(function (d) {
        document.getElementById('likeCount').textContent = Number(d.likes).toLocaleString();
        likeBtn.className = 'btn ' + (d.liked ? 'btn-danger' : 'btn-outline-danger');
        likeBtn.setAttribute('aria-pressed', d.liked ? 'true' : 'false');
        likeBtn.querySelector('i').className = 'bi ' + (d.liked ? 'bi-heart-fill' : 'bi-heart');
      })
      .catch(function () {});
  });

  var parent = document.getElementById('c_parent');
  var note = document.getElementById('replyNote');
  var cancel = document.getElementById('cancelReply');
  var msg = document.getElementById('commentMsg');

  function clearReply() { parent.value = ''; note.classList.add('d-none'); cancel.classList.add('d-none'); }
  cancel.addEventListener('click', clearReply);

  document.querySelectorAll('.reply-btn').forEach(function (b) {
    b.addEventListener('click', function () {
      parent.value = b.dataset.id;
      note.textContent = 'Replying to ' + b.dataset.name;
      note.classList.remove('d-none');
      cancel.classList.remove('d-none');
      document.getElementById('commentForm').scrollIntoView({behavior: 'smooth', block: 'center'});
      document.getElementById('c_body').focus();
    });
  });

  document.getElementById('commentForm').addEventListener('submit', function (e) {
    e.preventDefault();
    msg.textContent = '';
    var payload = {
      name: document.getElementById("c_name").value,
      body: document.getElementById('c_body').value,
      parent_id: parent.value ? parseInt(parent.value, 10) : null
    };
    fetch(base + '/comment', {method: 'POST', headers: headers, body: JSON.stringify(payload)})
      .then(function (r) {
        if (r.status === 201) { window.location.hash = 'comments'; window.location.reload(); return null; }
        if (r.status === 429) { msg.textContent = 'Please wait a moment before commenting again.'; return null; }
        return r.json().then(function (j) {
          var first = j.errors ? Object.values(j.errors)[0][0] : (j.message || 'Could not post your comment.');
          msg.textContent = first;
        });
      })
      .catch(function () { msg.textContent = 'Could not post your comment. Please try again.'; });
  });
});
</script>
@endpush
