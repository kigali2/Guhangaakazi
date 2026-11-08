<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', config('site.name')) | {{ config('site.name') }}</title>
<meta name="description" content="@yield('description', config('site.description'))">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:site_name" content="{{ config('site.name') }}">
<meta property="og:type" content="website">
<meta property="og:title" content="@yield('title', config('site.name'))">
<meta property="og:description" content="@yield('description', config('site.description'))">
<meta property="og:url" content="{{ url()->current() }}">
@if (trim($__env->yieldContent('image')) !== '')
<meta property="og:image" content="@yield('image')">
@endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-xl navbar-dark site-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('site.home') }}"><i class="bi bi-tree-fill text-warning"></i> {{ config('site.name') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteMenu" aria-controls="siteMenu" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="siteMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                @foreach (config('site.nav') as $n)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs($n[2]) ? 'active' : '' }}" href="{{ route($n[1]) }}">
                            @if ($n[3])<i class="bi {{ $n[3] }}"></i> @endif{{ $n[0] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
@yield('content')
</main>

<footer class="site-footer mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <div class="fs-4 fw-bold text-white mb-2"><i class="bi bi-tree-fill text-warning"></i> {{ config('site.name') }}</div>
                <p class="small">{{ config('site.description') }}</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @foreach (config('site.contacts') as $c)
                        <a class="social-btn" href="{{ $c[2] }}" aria-label="{{ $c[0] }}" title="{{ $c[0] }}" @if (str_starts_with($c[2], 'http')) target="_blank" rel="noopener noreferrer" @endif><i class="bi {{ $c[1] }}"></i></a>
                    @endforeach
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="fw-semibold text-white mb-3">Quick links</div>
                <ul class="list-unstyled row row-cols-2 row-cols-md-3 g-2 small mb-0">
                    @foreach (config('site.footer') as $f)
                        <li class="col"><a href="{{ route($f[1]) }}">{{ $f[0] }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <hr class="border-secondary mt-4">
        <div class="small text-center">&copy; {{ date('Y') }} {{ config('site.name') }}. All rights reserved.</div>
    </div>
</footer>
@include('public.partials.card-modal')
@include('public.partials.media-viewer')
@stack('scripts')
</body>
</html>
