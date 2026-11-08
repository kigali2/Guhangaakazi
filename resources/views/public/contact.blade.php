@extends('public.layouts.app')
@section('title', 'Contact Us')
@section('description', 'Reach GuhangaAkazi on WhatsApp, phone, email and social media.')
@section('content')
<section class="page-banner"><div class="container"><h1 class="fw-bold mb-1">Contact Us</h1><p class="mb-0">Tap an icon to reach us instantly.</p></div></section>
<div class="container py-5">
    <div class="row g-3 g-lg-4 justify-content-center">
        @foreach ($contacts as $c)
            <div class="col-6 col-md-4 col-lg-3">
                <a class="contact-card" href="{{ $c[2] }}" aria-label="Contact us on {{ $c[0] }}" @if (str_starts_with($c[2], 'http')) target="_blank" rel="noopener noreferrer" @endif>
                    <i class="bi {{ $c[1] }}" style="color: {{ $c[3] }}"></i>
                    <span>{{ $c[0] }}</span>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
