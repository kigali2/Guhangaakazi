@extends('admin.layouts.app')
@section('title', 'Slide')
@section('content')
@php
    $action = $slide->exists ? route('admin.slides.update', $slide) : route('admin.slides.store');
    $img = old('image', $slide->image_key ? \App\Support\R2::url($slide->image_key) : '');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">{{ $slide->exists ? 'Edit' : 'Add' }} slide</h1>
    <a href="{{ route('admin.slides.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ $action }}" class="card border-0 shadow-sm"><div class="card-body">
    @csrf
    @if ($slide->exists)
        @method('PUT')
    @endif
    <div class="mb-3">
        <label class="form-label" for="s_img">Image link (from your R2 bucket)</label>
        <input id="s_img" type="text" name="image" value="{{ $img }}" class="form-control" placeholder="https://pub-xxxx.r2.dev/slide.jpg" required>
        @if ($img)<img src="{{ $img }}" alt="Preview" class="mt-2 rounded" style="max-height:140px;max-width:100%">@endif
    </div>
    <div class="mb-3">
        <label class="form-label" for="s_alt">Image description (alt text)</label>
        <input id="s_alt" type="text" name="alt_text" value="{{ old('alt_text', $slide->alt_text) }}" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label" for="s_title">Heading (optional, uses the home heading if empty)</label>
        <input id="s_title" type="text" name="title" value="{{ old('title', $slide->title) }}" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label" for="s_desc">Description (optional)</label>
        <textarea id="s_desc" name="description" rows="3" class="form-control">{{ old('description', $slide->description) }}</textarea>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-5">
            <label class="form-label" for="s_bl">Button label (optional)</label>
            <input id="s_bl" type="text" name="button_label" value="{{ old('button_label', $slide->button_label) }}" class="form-control" placeholder="Explore Business Ideas">
        </div>
        <div class="col-12 col-md-7">
            <label class="form-label" for="s_bk">Button page (a path like /business-ideas, or a full link)</label>
            <input id="s_bk" type="text" name="button_link" value="{{ old('button_link', $slide->button_link) }}" class="form-control" placeholder="/business-ideas">
        </div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="s_act" @checked(old('is_active', $slide->exists ? $slide->is_active : true))>
        <label class="form-check-label" for="s_act">Active (shown on the home page)</label>
    </div>
    <button class="btn btn-primary btn-lg w-100">Save slide</button>
</div></form>
@endsection
