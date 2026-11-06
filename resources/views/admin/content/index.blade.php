@extends('admin.layouts.app')
@section('title', $cfg['label'])
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h4 mb-0">{{ $cfg['label'] }}</h1>
    <a href="{{ route('admin.' . $res . '.create') }}" class="btn btn-primary">+ New {{ $cfg['singular'] }}</a>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-8 col-md-5">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by title">
    </div>
    <div class="col-4 col-md-3">
        <select name="status" class="form-select">
            <option value="">All</option>
            <option value="published" @selected(request('status') === 'published')>Published</option>
            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
        </select>
    </div>
    <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>

<div class="row g-3">
    @forelse ($items as $item)
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-2">
                        <div style="min-width:0">
                            <div class="fw-semibold text-truncate">{{ $item->title }}</div>
                            <div class="small text-muted">{{ $item->category?->name ?? 'No category' }}</div>
                        </div>
                        <div class="text-end">
                            @if ($item->status === 'published' && $item->published_at && $item->published_at->isFuture())
                                <span class="badge text-bg-info">Scheduled</span>
                            @elseif ($item->status === 'published')
                                <span class="badge text-bg-success">Published</span>
                            @else
                                <span class="badge text-bg-secondary">Draft</span>
                            @endif
                            @if ($item->is_featured)
                                <span class="badge text-bg-warning">Featured</span>
                            @endif
                        </div>
                    </div>
                    <div class="small text-muted mt-2">{{ number_format($item->views_count) }} views, {{ number_format($item->likes_count) }} likes, {{ number_format($item->comments_count) }} comments</div>
                    <div class="d-flex flex-wrap gap-1 mt-3">
                        <a href="{{ route('admin.' . $res . '.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('admin.' . $res . '.publish', $item->id) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-success">{{ $item->status === 'published' ? 'Unpublish' : 'Publish' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.' . $res . '.feature', $item->id) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning">{{ $item->is_featured ? 'Unfeature' : 'Feature' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.' . $res . '.destroy', $item->id) }}" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">Nothing here yet. Tap "New {{ $cfg['singular'] }}" to add the first one.</div></div>
    @endforelse
</div>

<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>

<script>
document.querySelectorAll('.delete-form').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!confirm('Delete this item? It will be removed from the website.')) { e.preventDefault(); }
  });
});
</script>
@endsection
