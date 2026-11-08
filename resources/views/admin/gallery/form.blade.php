@extends('admin.layouts.app')
@section('title', $cfg['singular'])
@section('content')
@php
    $action = $item->exists ? route('admin.' . $res . '.update', $item->id) : route('admin.' . $res . '.store');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">{{ $item->exists ? 'Edit' : 'New' }} {{ $cfg['singular'] }}</h1>
    <a href="{{ route('admin.' . $res . '.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($item->exists)
        @method('PUT')
    @endif

    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="mb-3">
            <label class="form-label" for="g_title">Title</label>
            <input id="g_title" type="text" name="title" value="{{ old('title', $item->title) }}" class="form-control @error('title') is-invalid @enderror" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="g_slug">URL slug (leave empty to generate from the title)</label>
            <input id="g_slug" type="text" name="slug" value="{{ old('slug', $item->slug) }}" class="form-control @error('slug') is-invalid @enderror">
            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="g_cat">Category</label>
            <select id="g_cat" name="category_id" class="form-select">
                <option value="">No category</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) old('category_id', $item->category_id) === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-0">
            <label class="form-label" for="g_desc">Description</label>
            <textarea id="g_desc" name="description" rows="4" class="form-control">{{ old('description', $item->description) }}</textarea>
        </div>
    </div></div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <h2 class="h6 mb-3">Files (upload in Cloudflare, then paste the public link)</h2>
        @foreach ($cfg['links'] as $l)
            @php $lv = old($l[0], $item->{$l[1]} ? \App\Support\R2::url($item->{$l[1]}) : ''); @endphp
            <div class="mb-3">
                <label class="form-label" for="g_{{ $l[0] }}">{{ $l[2] }}</label>
                <input id="g_{{ $l[0] }}" type="text" name="{{ $l[0] }}" value="{{ $lv }}" class="form-control @error($l[0]) is-invalid @enderror" placeholder="https://pub-xxxx.r2.dev/file">
                @error($l[0])<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($lv && $l[0] !== 'video')
                    <img src="{{ $lv }}" alt="Preview" class="mt-2 rounded" style="max-height:140px;max-width:100%">
                @endif
            </div>
        @endforeach
        @foreach ($cfg['extra'] as $e)
            <div class="mb-3">
                <label class="form-label" for="g_{{ $e[0] }}">{{ $e[1] }}</label>
                <input id="g_{{ $e[0] }}" type="{{ $e[2] }}" name="{{ $e[0] }}" value="{{ old($e[0], $item->{$e[0]}) }}" class="form-control @error($e[0]) is-invalid @enderror">
                @error($e[0])<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
    </div></div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="g_status">Status</label>
                <select id="g_status" name="status" class="form-select">
                    <option value="draft" @selected(old('status', $item->status ?: 'draft') === 'draft')>Draft (private)</option>
                    <option value="published" @selected(old('status', $item->status) === 'published')>Published</option>
                </select>
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="g_feat" @checked(old('is_featured', $item->is_featured))>
                    <label class="form-check-label" for="g_feat">Featured on the home page</label>
                </div>
            </div>
        </div>
    </div></div>

    <button class="btn btn-primary btn-lg w-100 mb-4">Save</button>
</form>
@endsection
