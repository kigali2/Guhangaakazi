@extends('admin.layouts.app')
@section('title', 'Content Linking')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h4 mb-0">Content Linking</h1>
    <a href="{{ route('admin.links.create') }}" class="btn btn-primary">+ Link media</a>
</div>
<form method="GET" class="mb-3">
    <select name="type" class="form-select" onchange="this.form.submit()" aria-label="Filter by content type">
        <option value="">All content types</option>
        @foreach ($types as $key => $cfg)
            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $cfg['label'] }}</option>
        @endforeach
    </select>
</form>
<div class="row g-3">
    @forelse ($links as $l)
        @php
            $label = data_get(collect($types)->first(fn ($c) => $c['model'] === $l->linkable_type), 'label', 'Content');
            $kind = $l->media->type;
        @endphp
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex gap-3">
                @if ($kind === 'image')
                    <img src="{{ $l->media->url }}" alt="" style="width:90px;height:70px;object-fit:cover" class="rounded flex-shrink-0">
                @else
                    <div class="bg-light text-muted fw-semibold rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:90px;height:70px">{{ strtoupper($kind === 'document' ? 'PDF' : $kind) }}</div>
                @endif
                <div style="min-width:0" class="flex-grow-1">
                    <div class="text-truncate fw-semibold">{{ $l->media->original_name }}</div>
                    <div class="small text-muted text-truncate">{{ $label }}: {{ $l->linkable->title ?? 'Deleted item' }}</div>
                    <span class="badge text-bg-success">{{ $roles[$l->role][0] ?? $l->role }}</span>
                    @if ($l->caption)<span class="small text-muted">{{ $l->caption }}</span>@endif
                    <div class="d-flex gap-1 mt-2">
                        <a href="{{ route('admin.links.edit', $l) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('admin.links.destroy', $l) }}" class="remove-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove link</button></form>
                    </div>
                </div>
            </div></div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">No links yet. Tap "+ Link media" to connect a file to a business idea, skill, opportunity or article.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $links->links('pagination::bootstrap-5') }}</div>
<script>
document.querySelectorAll('.remove-form').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    if (!confirm('Remove this link? The file stays in the Media Library.')) { e.preventDefault(); }
  });
});
</script>
@endsection
