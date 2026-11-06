@extends('admin.layouts.app')
@section('title', 'Categories')
@section('content')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6">Add a category</h2>
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <div class="mb-2"><input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Category name" required></div>
            <div class="mb-2"><textarea name="description" rows="2" class="form-control" placeholder="Description (optional)">{{ old('description') }}</textarea></div>
            <div class="mb-2"><input type="text" name="image" value="{{ old('image') }}" class="form-control" placeholder="Image link from R2 (optional)"></div>
            <button class="btn btn-primary">Add category</button>
        </form>
    </div>
</div>

<div class="list-group shadow-sm mb-3">
    @forelse ($items as $c)
        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
            <div style="min-width:0">
                <div class="fw-semibold text-truncate">{{ $c->name }}</div>
                <div class="small text-muted text-truncate">{{ $c->description }}</div>
            </div>
            <div class="d-flex gap-1">
                <a href="{{ route('admin.categories.edit', $c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    @empty
        <div class="list-group-item text-muted">No categories yet.</div>
    @endforelse
</div>

{{ $items->links('pagination::bootstrap-5') }}

<script>
document.querySelectorAll('.delete-form').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!confirm('Delete this category?')) { e.preventDefault(); }
  });
});
</script>
@endsection
