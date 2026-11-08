@extends('admin.layouts.app')
@section('title', 'Homepage Slider')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h4 mb-0">Homepage Slider</h1>
    <a href="{{ route('admin.slides.create') }}" class="btn btn-primary">+ Add slide</a>
</div>
<p class="text-muted small">Active slides change every 5 seconds on the home page, in this order.</p>

<div class="row g-3">
    @forelse ($slides as $s)
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <img src="{{ \App\Support\R2::url($s->image_key) }}" alt="{{ $s->alt_text ?: $s->title }}" class="card-img-top" style="height:160px;object-fit:cover">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-2">
                        <div class="fw-semibold text-truncate">{{ $s->title ?: 'No title' }}</div>
                        <span class="badge text-bg-{{ $s->is_active ? 'success' : 'secondary' }}">{{ $s->is_active ? 'Active' : 'Hidden' }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-3">
                        <form method="POST" action="{{ route('admin.slides.move', [$s, 'up']) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Up</button></form>
                        <form method="POST" action="{{ route('admin.slides.move', [$s, 'down']) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Down</button></form>
                        <form method="POST" action="{{ route('admin.slides.toggle', $s) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ $s->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                        <a href="{{ route('admin.slides.edit', $s) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('admin.slides.destroy', $s) }}" class="delete-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">No slides yet. The home page shows the default green banner until you add one.</div></div>
    @endforelse
</div>

<script>
document.querySelectorAll('.delete-form').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!confirm('Delete this slide?')) { e.preventDefault(); }
  });
});
</script>
@endsection
