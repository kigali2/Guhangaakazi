@extends('admin.layouts.app')
@section('title', 'SEO Management')
@section('content')
<h1 class="h4 mb-3">SEO Management</h1>
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ route('admin.seo.update') }}" class="card border-0 shadow-sm mb-3"><div class="card-body">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label" for="seo_title">Default page title</label>
        <input id="seo_title" type="text" name="seo_title" value="{{ old('seo_title', $values['seo_title'] ?? '') }}" class="form-control" maxlength="120">
    </div>
    <div class="mb-3">
        <label class="form-label" for="seo_description">Default meta description (up to 320 characters)</label>
        <textarea id="seo_description" name="seo_description" rows="3" class="form-control" maxlength="320">{{ old('seo_description', $values['seo_description'] ?? '') }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label" for="seo_image">Default sharing image link (from your R2 bucket)</label>
        <input id="seo_image" type="text" name="seo_image" value="{{ old('seo_image', $imageUrl) }}" class="form-control" placeholder="https://pub-xxxx.r2.dev/share.jpg">
        @if ($imageUrl)<img src="{{ $imageUrl }}" alt="Preview" class="mt-2 rounded" style="max-height:120px;max-width:100%">@endif
    </div>
    <hr>
    <div class="mb-3">
        <label class="form-label" for="gsc_verification">Google Search Console verification code</label>
        <input id="gsc_verification" type="text" name="gsc_verification" value="{{ old('gsc_verification', $values['gsc_verification'] ?? '') }}" class="form-control" placeholder="Paste the content value from Google">
        <div class="form-text">In Search Console choose the "HTML tag" method and paste the code from <code>content="..."</code>.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="ga_measurement_id">Google Analytics Measurement ID</label>
        <input id="ga_measurement_id" type="text" name="ga_measurement_id" value="{{ old('ga_measurement_id', $values['ga_measurement_id'] ?? '') }}" class="form-control" placeholder="G-XXXXXXXXXX" maxlength="30">
    </div>
    <button class="btn btn-primary btn-lg w-100">Save SEO settings</button>
</div></form>

<div class="card border-0 shadow-sm"><div class="card-body">
    <h2 class="h6">Search engine files</h2>
    <p class="mb-1 small">Sitemap: <a href="{{ url('/sitemap.xml') }}" target="_blank">{{ url('/sitemap.xml') }}</a></p>
    <p class="mb-0 small">Robots: <a href="{{ url('/robots.txt') }}" target="_blank">{{ url('/robots.txt') }}</a></p>
</div></div>
@endsection
