@extends('admin.layouts.app')
@section('title', 'Website Settings')
@section('content')
<h1 class="h4 mb-3">Website Settings</h1>
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ route('admin.settings.update') }}" class="card border-0 shadow-sm"><div class="card-body">
    @csrf
    @method('PUT')
    @foreach ($fields as $key => $f)
        @php $val = old($key, $values[$key] ?? $defaults[$key] ?? ''); @endphp
        <div class="mb-3">
            <label class="form-label" for="st_{{ $key }}">{{ $f[0] }}</label>
            @if ($f[1] === 'textarea')
                <textarea id="st_{{ $key }}" name="{{ $key }}" rows="{{ in_array($key, ['hero_description']) ? 3 : 6 }}" class="form-control @error($key) is-invalid @enderror">{{ $val }}</textarea>
            @else
                <input id="st_{{ $key }}" type="{{ $f[1] }}" name="{{ $key }}" value="{{ $val }}" class="form-control @error($key) is-invalid @enderror">
            @endif
            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endforeach
    <p class="small text-muted">Leave a text box empty to use the default text. Use a blank line to start a new paragraph.</p>
    <button class="btn btn-primary btn-lg w-100">Save settings</button>
</div></form>
@endsection
