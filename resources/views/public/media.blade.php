@extends('public.layouts.app')
@section('title', $item->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) ($item->description ?: $item->title)), 160))
@section('image', $res === 'photos' ? $imageUrl : ($poster ?? ''))
@section('content')
@php
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($item->title);
@endphp
<div class="container py-4 py-lg-5">

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            @if ($item->category)<span class="badge text-bg-success mb-2">{{ $item->category->name }}</span>@endif
            <h1 class="fw-bold">{{ $item->title }}</h1>

            @if ($res === 'photos')
                <img src="{{ $imageUrl }}" alt="{{ $item->alt_text ?: $item->title }}" class="img-fluid rounded mb-3 w-100">
                <a href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mb-3"><i class="bi bi-arrows-fullscreen"></i> View full size</a>
            @else
                @if ($embed)
                    <div class="ratio ratio-16x9 mb-3"><iframe src="{{ $embed }}" title="{{ $item->title }}" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
                @elseif ($videoUrl)
                    <video controls preload="none" class="w-100 rounded mb-3" @if ($poster) poster="{{ $poster }}" @endif><source src="{{ $videoUrl }}"></video>
                @endif
            @endif

            @if ($item->description)<p class="reading">{!! nl2br(e($item->description)) !!}</p>@endif

            <div class="d-flex align-items-center gap-2 mt-3">
                <span class="small text-muted">Share</span>
                <a class="social-btn dark" href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"><i class="bi bi-whatsapp"></i></a>
                <a class="social-btn dark" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="bi bi-facebook"></i></a>
                <a class="social-btn dark" href="https://x.com/intent/post?text={{ $shareText }}&url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X"><i class="bi bi-twitter-x"></i></a>
            </div>

            @include('public.partials.engage', ['type' => $res, 'item' => $item, 'liked' => $liked, 'comments' => $comments])
        </div>

        <aside class="col-12 col-lg-4">
            @if ($similar->isNotEmpty())
                <h2 class="h5 mb-3">More {{ strtolower($label) }}</h2>
                <div class="d-grid gap-3">
                    @foreach ($similar as $c)@include('public.partials.card', ['c' => $c])@endforeach
                </div>
            @endif
        </aside>
    </div>

    @foreach ($related as $r)
        <section class="mt-5">
            <h2 class="h4 mb-3">{{ $r['title'] }}</h2>
            <div class="row g-4">
                @foreach ($r['cards'] as $c)<div class="col-12 col-md-6 col-lg-4">@include('public.partials.card', ['c' => $c])</div>@endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
