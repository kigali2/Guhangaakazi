@extends('public.layouts.app')
@section('title', $heading)
@section('description', $intro)
@section('content')
<h1 class="visually-hidden">{{ config('site.name') }}</h1>
<section class="hero" aria-label="Welcome">
    @if ($slides->count())
        <div id="heroCarousel" class="carousel slide carousel-fade" @if ($slides->count() > 1) data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="false" @endif>
            @if ($slides->count() > 1)
                <div class="carousel-indicators">
                    @foreach ($slides as $i => $s)
                        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Slide {{ $i + 1 }}" @if ($i === 0) aria-current="true" @endif></button>
                    @endforeach
                </div>
            @endif
            <div class="carousel-inner">
                @foreach ($slides as $i => $s)
                    <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                        <img class="slide" src="{{ $s['image'] }}" alt="{{ $s['alt'] }}" @if ($i > 0) loading="lazy" @endif>
                        <div class="hero-overlay"></div>
                        <div class="hero-text">
                            @include('public.partials.hero', ['title' => $s['title'] ?: $heading, 'desc' => $s['description'] ?: $intro, 'btn' => $s['button']])
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($slides->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
            @endif
        </div>
        @if ($slides->count() > 1)
            <button type="button" id="heroToggle" class="btn btn-dark btn-sm hero-toggle" aria-label="Pause slideshow"><i class="bi bi-pause-fill"></i></button>
        @endif
    @else
        <div class="hero-text static">
            @include('public.partials.hero', ['title' => $heading, 'desc' => $intro, 'btn' => null])
        </div>
    @endif
</section>

@foreach ($sections as $s)
    <section class="py-5 {{ $loop->even ? 'bg-white' : '' }}">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4 gap-2">
                <h2 class="h3 fw-bold mb-0">{{ $s['title'] }}</h2>
                <a href="{{ $s['link'] }}" class="btn btn-outline-primary btn-sm">{{ $s['linkLabel'] }}</a>
            </div>
            <div class="row g-4">
                @foreach ($s['cards'] as $c)
                    <div class="col-12 col-md-6 col-lg-4">@include('public.partials.card', ['c' => $c])</div>
                @endforeach
            </div>
        </div>
    </section>
@endforeach

<section class="py-5 bg-white">
    <div class="container">
        <h2 class="h3 fw-bold text-center mb-2">Why {{ config('site.name') }}?</h2>
        <p class="text-center text-muted col-lg-8 mx-auto mb-4">{{ config('site.pages.mission') }}</p>
        <div class="row g-4">
            @foreach (config('site.why') as $w)
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="text-center p-3 h-100">
                        <div class="why-icon mx-auto mb-3"><i class="bi {{ $w[0] }}"></i></div>
                        <h3 class="h5">{{ $w[1] }}</h3>
                        <p class="text-muted small mb-0">{{ $w[2] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('heroCarousel');
  var btn = document.getElementById('heroToggle');
  if (!el || !btn || !window.bootstrap) { return; }
  var carousel = window.bootstrap.Carousel.getOrCreateInstance(el);
  var playing = true;
  function setState(on) {
    playing = on;
    if (on) { carousel.cycle(); } else { carousel.pause(); }
    btn.innerHTML = on ? '<i class="bi bi-pause-fill"></i>' : '<i class="bi bi-play-fill"></i>';
    btn.setAttribute('aria-label', on ? 'Pause slideshow' : 'Play slideshow');
  }
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { setState(false); }
  btn.addEventListener('click', function () { setState(!playing); });
});
</script>
@endpush
