@php
    $gsc = \App\Models\Setting::get('gsc_verification');
    $ga = \App\Models\Setting::get('ga_measurement_id');
    $seoImage = trim($__env->yieldContent('image'));
    if ($seoImage === '' && \App\Models\Setting::get('seo_image')) {
        $seoImage = \App\Support\R2::url(\App\Models\Setting::get('seo_image'));
    }
    $schemas = \App\Support\Seo::schemas($item ?? null, $res ?? null, $poster ?? null, $videoUrl ?? null, $embed ?? null);
@endphp
@if (request()->routeIs("site.search"))<meta name="robots" content="noindex, follow">@endif
@if ($gsc)<meta name="google-site-verification" content="{{ $gsc }}">@endif
@if ($seoImage)
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:image" content="{{ $seoImage }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('title', \App\Models\Setting::get('seo_title', config('site.name')))">
<meta name="twitter:description" content="@yield('description', \App\Models\Setting::get('seo_description', config('site.description')))">
@foreach ($schemas as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
@if ($ga && preg_match('/^G-[A-Za-z0-9]{4,20}$/', $ga))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '{{ $ga }}');</script>
@endif
