@extends('admin.layouts.app')
@section('title', 'Edit link')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Edit link</h1>
    <a href="{{ route('admin.links.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="card border-0 shadow-sm"><div class="card-body">
    @if ($link->media->type === 'image')
        <img src="{{ $link->media->url }}" alt="" class="rounded mb-3" style="max-height:160px;max-width:100%">
    @else
        <div class="bg-light rounded px-3 py-4 fw-semibold d-inline-block mb-3">{{ strtoupper($link->media->type === 'document' ? 'PDF' : $link->media->type) }}</div>
    @endif
    <p class="mb-1"><strong>File:</strong> {{ $link->media->original_name }}</p>
    <p class="mb-3"><strong>Linked to:</strong> {{ $link->linkable->title ?? 'Deleted item' }}</p>
    <form method="POST" action="{{ route('admin.links.update', $link) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label" for="e_role">Role</label>
            <select id="e_role" name="role" class="form-select">
                @foreach ($roles as $key => $r)<option value="{{ $key }}" @selected(old('role', $link->role) === $key)>{{ $r[0] }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="e_cap">Caption</label>
            <input id="e_cap" type="text" name="caption" value="{{ old('caption', $link->caption) }}" class="form-control" maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label" for="e_ord">Order (smaller numbers show first)</label>
            <input id="e_ord" type="number" name="sort_order" value="{{ old('sort_order', $link->sort_order) }}" class="form-control" min="0">
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</div></div>
@endsection
