<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield("title", "Dashboard") | GuhangaAkazi Admin</title>
@vite(["resources/scss/app.scss", "resources/js/app.js"])
</head>
<body class="bg-light">
<div class="d-flex">
<aside class="offcanvas-lg offcanvas-start admin-sidebar text-white" tabindex="-1" id="sidebar">
<div class="offcanvas-header d-lg-none"><h5 class="offcanvas-title">GuhangaAkazi</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button></div>
<div class="offcanvas-body flex-column p-3">
<div class="d-none d-lg-block fs-5 fw-bold mb-3">GuhangaAkazi</div>
<ul class="nav nav-pills flex-column gap-1 mb-3">
@foreach (config("adminnav") as $item)
@if (Route::has($item[1]))
<li><a class="nav-link {{ request()->routeIs($item[1]) ? "active" : "" }}" href="{{ route($item[1]) }}">{{ $item[0] }}</a></li>
@else
<li><span class="nav-link disabled">{{ $item[0] }} <span class="badge text-bg-secondary ms-1">Soon</span></span></li>
@endif
@endforeach
</ul>
<form method="POST" action="{{ route("admin.logout") }}" class="mt-auto">@csrf<button class="btn btn-outline-light w-100">Sign Out</button></form>
</div>
</aside>
<div class="flex-grow-1" style="min-width:0">
<header class="navbar bg-white border-bottom px-3 sticky-top">
<button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Open menu">Menu</button>
<span class="fw-semibold d-none d-lg-inline">@yield("title", "Dashboard")</span>
<div class="dropdown ms-auto">
<button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">@if (auth()->user()->profile_image_key)<img src="{{ \App\Support\R2::url(auth()->user()->profile_image_key) }}" alt="" class="rounded-circle me-1" width="24" height="24" style="object-fit:cover">@endif{{ auth()->user()->name }}</button>
<ul class="dropdown-menu dropdown-menu-end">
<li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
<li><hr class="dropdown-divider"></li>
<li><form method="POST" action="{{ route("admin.logout") }}">@csrf<button class="dropdown-item">Sign Out</button></form></li>
</ul></div></header>
<main class="p-3 p-lg-4">
@if (session("success"))
<div class="alert alert-success">{{ session("success") }}</div>
@endif
@yield("content")
</main></div></div>
@include("admin.partials.rte")
</body></html>
