@extends('public.layouts.app')
@section('title', $item->seo_title ?: $item->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) ($item->meta_description ?: ($item->summary ?: $item->title))), 160))
@section('image', $item->cover_image_key ? \App\Support\R2::url($item->cover_image_key) : '')
@section('content')
@php
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($item->title);
    $cover = $item->cover_image_key ? \App\Support\R2::url($item->cover_image_key) : null;
@endphp
<div class="container py-4 py-lg-5">

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="mb-2">
                @if ($item->category)<span class="badge text-bg-success">{{ $item->category->name }}</span>@endif
                @if ($status)<span class="badge text-bg-{{ $status[1] }}">{{ $status[0] }}</span>@endif
            </div>
            <h1 class="fw-bold">{{ $item->title }}</h1>
            <div class="text-muted small mb-3">
                @if ($item->published_at)<i class="bi bi-calendar3"></i> {{ $item->published_at->format('d M Y') }}@endif
                @if ($res === 'articles')
                    &nbsp;<i class="bi bi-clock"></i> {{ $item->reading_time ?: 1 }} min read
                    @if ($item->author)&nbsp;<i class="bi bi-person"></i> {{ $item->author->name }}@endif
                @endif
            </div>

            @if ($cover)
                <img src="{{ $cover }}" alt="{{ $item->cover_image_alt ?: $item->title }}" class="img-fluid rounded mb-4 w-100">
            @endif

            @if ($item->summary)<p class="lead">{{ $item->summary }}</p>@endif

            @if ($res === 'opportunities')
                <div class="card border-0 shadow-sm mb-4"><div class="card-body">
                    <div class="row g-3">
                        @if ($item->opens_at)<div class="col-6"><div class="small text-muted">Opens</div><div class="fw-semibold">{{ $item->opens_at->format('d M Y') }}</div></div>@endif
                        @if ($item->closes_at)<div class="col-6"><div class="small text-muted">Closes</div><div class="fw-semibold">{{ $item->closes_at->format('d M Y') }}</div></div>@endif
                    </div>
                    @if ($item->application_url)
                        <a href="{{ $item->application_url }}" target="_blank" rel="nofollow noopener noreferrer" class="btn btn-warning btn-lg mt-3"><i class="bi bi-box-arrow-up-right"></i> Apply Now</a>
                    @endif
                </div></div>
            @endif

            @if ($res === 'business-ideas')
                @php
                    $rows = collect($cfg['fields'])->where(2, 'number')->filter(fn ($f) => $item->{$f[0]} !== null);
                @endphp
                @if ($rows->isNotEmpty())
                    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
                        <h2 class="h5">Financial information (estimates)</h2>
                        <table class="table table-sm mb-2"><tbody>
                            @foreach ($rows as $f)
                                <tr><th scope="row" class="fw-normal">{{ \Illuminate\Support\Str::before($f[1], ' (') }}</th><td class="text-end fw-semibold">{{ $item->currency ?: 'RWF' }} {{ number_format((float) $item->{$f[0]}) }}</td></tr>
                            @endforeach
                        </tbody></table>
                        <p class="small text-muted mb-0">These figures are estimates for guidance only. Revenue is not the same as profit. Real results depend on your location, costs and market.</p>
                    </div></div>
                @endif
            @endif

            <div class="reading">
                @foreach ($cfg['fields'] as $f)
                    @php $v = $item->{$f[0]}; @endphp
                    @if ($f[2] === 'textarea' && filled($v))
                        @if ($f[0] !== 'content')<h2 class="h4 mt-4">{{ $f[1] }}</h2>@endif
                        <p>{!! nl2br(e($v)) !!}</p>
                    @endif
                @endforeach
            </div>

            <div class="d-flex align-items-center gap-2 mt-4">
                <span class="small text-muted">Share</span>
                <a class="social-btn dark" href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"><i class="bi bi-whatsapp"></i></a>
                <a class="social-btn dark" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"><i class="bi bi-facebook"></i></a>
                <a class="social-btn dark" href="https://x.com/intent/post?text={{ $shareText }}&url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X"><i class="bi bi-twitter-x"></i></a>
            </div>

            @include('public.partials.engage', ['type' => $res, 'item' => $item, 'liked' => $liked, 'comments' => $comments])
        </div>

        <aside class="col-12 col-lg-4">
            @if ($similar->isNotEmpty())
                <h2 class="h5 mb-3">More like this</h2>
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
