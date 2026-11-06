@extends('admin.layouts.app')
@section('title', $cfg['singular'])
@section('content')
@php
    $action = $item->exists ? route('admin.' . $res . '.update', $item->id) : route('admin.' . $res . '.store');
    $coverValue = old('cover_image', $item->cover_image_key ? \App\Support\R2::url($item->cover_image_key) : '');
    $publishedAt = old('published_at', $item->published_at ? $item->published_at->format('Y-m-d\TH:i') : '');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">{{ $item->exists ? 'Edit' : 'New' }} {{ $cfg['singular'] }}</h1>
    <a href="{{ route('admin.' . $res . '.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">Please fix the highlighted fields.</div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($item->exists)
        @method('PUT')
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Basic information</h2>

            <div class="mb-3">
                <label class="form-label" for="f_title">Title</label>
                <input id="f_title" type="text" name="title" value="{{ old('title', $item->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="f_slug">URL slug (leave empty to generate from the title)</label>
                <input id="f_slug" type="text" name="slug" value="{{ old('slug', $item->slug) }}" class="form-control @error('slug') is-invalid @enderror" placeholder="poultry-farming">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="f_category">Category</label>
                <select id="f_category" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                    <option value="">No category</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected((string) old('category_id', $item->category_id) === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="f_summary">Short description</label>
                <textarea id="f_summary" name="summary" rows="3" class="form-control @error('summary') is-invalid @enderror">{{ old('summary', $item->summary) }}</textarea>
                @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="f_cover">Cover image link (from your R2 bucket)</label>
                <input id="f_cover" type="text" name="cover_image" value="{{ $coverValue }}" class="form-control @error('cover_image') is-invalid @enderror" placeholder="https://pub-xxxx.r2.dev/photo.jpg">
                @error('cover_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($coverValue)
                    <img src="{{ $coverValue }}" alt="Cover preview" class="mt-2 rounded" style="max-height:140px;max-width:100%">
                @endif
            </div>

            <div class="mb-0">
                <label class="form-label" for="f_alt">Cover image description (alt text)</label>
                <input id="f_alt" type="text" name="cover_image_alt" value="{{ old('cover_image_alt', $item->cover_image_alt) }}" class="form-control">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Details</h2>
            @foreach ($cfg['fields'] as $f)
                @php
                    $name = $f[0];
                    $label = $f[1];
                    $type = $f[2];
                    $val = old($name, $item->{$name});
                    if ($val instanceof \Carbon\CarbonInterface) {
                        $val = $val->format('Y-m-d');
                    }
                @endphp
                <div class="mb-3">
                    <label class="form-label" for="f_{{ $name }}">{{ $label }}</label>
                    @if ($type === 'textarea')
                        <textarea id="f_{{ $name }}" name="{{ $name }}" rows="5" class="form-control @error($name) is-invalid @enderror">{{ $val }}</textarea>
                    @elseif ($type === 'select')
                        <select id="f_{{ $name }}" name="{{ $name }}" class="form-select @error($name) is-invalid @enderror">
                            <option value="">Automatic (from dates)</option>
                            @foreach ($f[4] as $optValue => $optLabel)
                                <option value="{{ $optValue }}" @selected((string) $val === (string) $optValue)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="f_{{ $name }}" type="{{ $type }}" @if ($type === 'number') step="any" @endif name="{{ $name }}" value="{{ $val }}" class="form-control @error($name) is-invalid @enderror">
                    @endif
                    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Publishing</h2>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="f_status">Status</label>
                    <select id="f_status" name="status" class="form-select">
                        <option value="draft" @selected(old('status', $item->status ?: 'draft') === 'draft')>Draft (private)</option>
                        <option value="published" @selected(old('status', $item->status) === 'published')>Published</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="f_pub">Publish date (optional, a future date schedules it)</label>
                    <input id="f_pub" type="datetime-local" name="published_at" value="{{ $publishedAt }}" class="form-control @error('published_at') is-invalid @enderror">
                    @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="f_feat" @checked(old('is_featured', $item->is_featured))>
                        <label class="form-check-label" for="f_feat">Featured on the homepage</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">SEO (optional)</h2>
            <div class="mb-3">
                <label class="form-label" for="f_seo">SEO title</label>
                <input id="f_seo" type="text" name="seo_title" value="{{ old('seo_title', $item->seo_title) }}" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label" for="f_meta">Meta description</label>
                <textarea id="f_meta" name="meta_description" rows="2" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $item->meta_description) }}</textarea>
                @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-0">
                <label class="form-label" for="f_kw">Focus keyword</label>
                <input id="f_kw" type="text" name="focus_keyword" value="{{ old('focus_keyword', $item->focus_keyword) }}" class="form-control">
            </div>
        </div>
    </div>

    <button class="btn btn-primary btn-lg w-100 mb-4">Save</button>
</form>
@endsection
