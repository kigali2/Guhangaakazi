@extends('admin.layouts.app')
@section('title', 'Edit category')
@section('content')
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.categories.update', $item) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="c_name">Name</label>
                <input id="c_name" type="text" name="name" value="{{ old('name', $item->name) }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="c_desc">Description</label>
                <textarea id="c_desc" name="description" rows="3" class="form-control">{{ old('description', $item->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="c_img">Image link from R2 (optional)</label>
                <input id="c_img" type="text" name="image" value="{{ old('image', $item->image_key ? \App\Support\R2::url($item->image_key) : '') }}" class="form-control">
            </div>
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
