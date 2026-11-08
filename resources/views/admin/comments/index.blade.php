@extends('admin.layouts.app')
@section('title', 'Comments')
@section('content')
@php
    $paths = ['BusinessIdea' => 'business-ideas', 'Skill' => 'skills', 'Opportunity' => 'opportunities', 'Article' => 'articles', 'Photo' => 'photos', 'Video' => 'videos'];
@endphp
<h1 class="h4 mb-3">Comments</h1>
<div class="btn-group mb-3">
    <a href="{{ route('admin.comments.index') }}" class="btn btn-sm {{ request('status') ? 'btn-outline-primary' : 'btn-primary' }}">All</a>
    <a href="{{ route('admin.comments.index', ['status' => 'approved']) }}" class="btn btn-sm {{ request('status') === 'approved' ? 'btn-primary' : 'btn-outline-primary' }}">Approved</a>
    <a href="{{ route('admin.comments.index', ['status' => 'rejected']) }}" class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-primary' : 'btn-outline-primary' }}">Rejected</a>
</div>

<div class="row g-3">
    @forelse ($items as $c)
        @php
            $target = $c->commentable;
            $path = $paths[class_basename($c->commentable_type)] ?? null;
        @endphp
        <div class="col-12">
            <div class="card border-0 shadow-sm"><div class="card-body">
                <div class="d-flex justify-content-between gap-2">
                    <div>
                        <strong>{{ $c->name }}</strong>
                        <span class="text-muted small">{{ $c->created_at->diffForHumans() }}</span>
                        @if ($c->parent_id)<span class="badge text-bg-light">Reply</span>@endif
                        @if ($c->is_reported)<span class="badge text-bg-danger">Reported</span>@endif
                    </div>
                    <span class="badge text-bg-{{ $c->status === 'approved' ? 'success' : 'secondary' }}">{{ ucfirst($c->status) }}</span>
                </div>
                <div class="my-2">{!! nl2br(e($c->body)) !!}</div>
                <div class="small text-muted mb-2">
                    On: {{ class_basename($c->commentable_type) }}
                    @if ($target && $path)
                        - <a href="{{ route('site.' . $path . '.show', $target->slug) }}" target="_blank">{{ $target->title }}</a>
                    @endif
                </div>
                <div class="d-flex gap-1">
                    @if ($c->status !== 'approved')
                        <form method="POST" action="{{ route('admin.comments.status', $c) }}">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-outline-success">Approve</button></form>
                    @endif
                    @if ($c->status !== 'rejected')
                        <form method="POST" action="{{ route('admin.comments.status', $c) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-outline-warning">Reject</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.comments.destroy', $c) }}" class="delete-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </div>
            </div></div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">No comments yet.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>

<script>
document.querySelectorAll('.delete-form').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!confirm('Delete this comment?')) { e.preventDefault(); }
  });
});
</script>
@endsection
