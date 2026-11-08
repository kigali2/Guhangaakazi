@extends('public.layouts.app')
@section('title', $title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($content), 155))
@section('content')
<section class="page-banner"><div class="container"><h1 class="fw-bold mb-0">{{ $title }}</h1></div></section>
<div class="container py-5">
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5 reading">
        @foreach (preg_split('/\R{2,}/', trim($content)) as $para)
            <p>{!! nl2br(e($para)) !!}</p>
        @endforeach
    </div></div>
</div>
@endsection
