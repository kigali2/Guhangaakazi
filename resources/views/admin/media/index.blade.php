@extends('admin.layouts.app')
@section('title', 'Media Library')
@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6">Add a file by link (upload in Cloudflare, then paste the public link)</h2>
        <form method="POST" action="{{ route('admin.media.link') }}" class="row g-2">
            @csrf
            <div class="col-12 col-md-7"><input type="text" name="url" value="{{ old('url') }}" class="form-control" placeholder="https://pub-xxxx.r2.dev/my-photo.jpg" required></div>
            <div class="col-8 col-md-3"><input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="Name (optional)"></div>
            <div class="col-4 col-md-2"><button class="btn btn-primary w-100">Add</button></div>
        </form>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-8 col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search files"></div>
    <div class="col-4 col-md-3">
        <select name="type" class="form-select">
            <option value="">All</option>
            <option value="image" @selected(request('type') === 'image')>Images</option>
            <option value="video" @selected(request('type') === 'video')>Videos</option>
            <option value="document" @selected(request('type') === 'document')>Documents</option>
        </select>
    </div>
    <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>

<div class="row g-3">
    @forelse ($items as $m)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                @if ($m->type === 'image')
                    <img src="{{ $m->url }}" alt="{{ $m->original_name }}" loading="lazy" class="card-img-top" style="height:150px;object-fit:cover">
                @elseif ($m->type === 'video')
                    <video src="{{ $m->url }}" preload="none" controls class="card-img-top" style="height:150px;background:#000"></video>
                @else
                    <div class="d-flex align-items-center justify-content-center bg-light text-muted fw-semibold" style="height:150px">PDF</div>
                @endif
                <div class="card-body p-2">
                    <div class="small text-truncate">{{ $m->original_name }}</div>
                    <div class="small text-muted">{{ ucfirst($m->type) }}</div>
                    <div class="d-flex gap-1 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill copy-link" data-url="{{ $m->url }}">Copy link</button>
                        <form method="POST" action="{{ route('admin.media.destroy', $m) }}" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">No files yet. Paste a link above.</div></div>
    @endforelse
</div>

<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>

<script>
document.querySelectorAll('.copy-link').forEach(function (b) {
  b.addEventListener('click', function () { navigator.clipboard.writeText(b.dataset.url); b.textContent = 'Copied'; });
});
document.querySelectorAll('.delete-form').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!confirm('Remove this file from the library? (It stays in your R2 bucket.)')) { e.preventDefault(); }
  });
});
</script>
@endsection
