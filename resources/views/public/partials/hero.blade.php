<div class="hero-content container py-5">
    <div class="hero-title display-5 fw-bold">{{ $title }}</div>
    <p class="lead col-lg-7 mt-3">{{ $desc }}</p>
    <div class="d-flex flex-wrap gap-2 mt-4">
        @if ($btn)
            <a class="btn btn-warning btn-lg" href="{{ $btn[1] }}">{{ $btn[0] }}</a>
        @else
            <a class="btn btn-warning btn-lg" href="{{ route('site.business-ideas.index') }}">Explore Business Ideas</a>
            <a class="btn btn-light btn-lg" href="{{ route('site.opportunities.index') }}">Discover Opportunities</a>
            <a class="btn btn-outline-light btn-lg" href="{{ route('site.skills.index') }}">Learn New Skills</a>
        @endif
    </div>
</div>
