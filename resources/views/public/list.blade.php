@extends('public.layouts.app')
@section('title', $label)
@section('description', $intro)
@section('content')
<section class="page-banner"><div class="container"><h1 class="fw-bold mb-1">{{ $label }}</h1><p class="mb-0">{{ $intro }}</p></div></section>
<div class="container py-4 py-lg-5">

    <div class="row g-4">
        @forelse ($items as $c)
            <div class="col-12 col-md-6 col-lg-4">@include('public.partials.card', ['c' => $c])</div>
        @empty
            <div class="col-12"><div class="alert alert-light border text-center">Nothing has been published here yet. Please check back soon.</div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $items->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
