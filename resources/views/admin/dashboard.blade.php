@extends("admin.layouts.app")
@section("title", "Dashboard")
@section("content")
<h1 class="h3 mb-3">Welcome back, {{ auth()->user()->name }}</h1>
<div class="row g-3 mb-4">@foreach ($stats as [$label, $value, $color])<div class="col-6 col-md-4 col-xl-3"><div class="card stat-card" style="border-left-color: var(--bs-{{ $color }})"><div class="card-body py-3"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold">{{ number_format($value) }}</div></div></div></div>@endforeach</div>
<div class="row g-3">@foreach ($recent as $heading => $rows)<div class="col-12 col-lg-6"><div class="card h-100 border-0 shadow-sm"><div class="card-header bg-white fw-semibold">{{ $heading }}</div><ul class="list-group list-group-flush">
@forelse ($rows as $row)<li class="list-group-item d-flex justify-content-between gap-2"><div style="min-width:0"><div class="text-truncate">{{ $row["title"] }}</div><small class="text-muted">{{ $row["at"]->diffForHumans() }}</small></div>@if ($row["badge"])<span class="badge text-bg-secondary">{{ ucfirst($row["badge"]) }}</span>@endif</li>@empty<li class="list-group-item text-muted">Nothing yet.</li>@endforelse
</ul></div></div>@endforeach</div>
@endsection
