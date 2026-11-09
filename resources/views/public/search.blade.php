@extends('public.layouts.app')
@section('title', $term !== '' ? 'Search: ' . $term : 'Search')
@section('description', 'Search business ideas, skills, opportunities, articles, photos and videos on GuhangaAkazi.')
@section('content')
<section class="page-banner">
    <div class="container">
        <h1 class="fw-bold mb-1">{{ $term !== '' ? 'Results for "' . $term . '"' : 'Search' }}</h1>
        @if ($term !== '')<p class="mb-0">{{ $total }} {{ $total === 1 ? 'result' : 'results' }} found</p>@endif
    </div>
</section>
<div class="container py-4 py-lg-5">
    <button type="button" class="search-pill js-open-search w-100 text-start mb-4" aria-label="Search the website"><i class="bi bi-search me-2"></i>Search again</button>
    @forelse ($sections as $s)
        <h2 class="h4 mb-3 {{ $loop->first ? '' : 'mt-5' }}">{{ $s['title'] }}</h2>
        <div class="row g-4">
            @foreach ($s['cards'] as $c)
                <div class="col-12 col-md-6 col-lg-4">@include('public.partials.card', ['c' => $c])</div>
            @endforeach
        </div>
    @empty
        <div class="alert alert-light border text-center">
            {{ $term !== '' ? 'No results found. Try a different word.' : 'Tap the search box and start typing.' }}
        </div>
    @endforelse
</div>
@endsection
