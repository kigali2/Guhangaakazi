<article class="card item-card h-100">
    @if ($c['type'] === 'photos' && $c['photo'])
        <button type="button" class="photo-open js-photo" data-full="{{ $c['photo'] }}" data-title="{{ $c['title'] }}" data-type="photos" data-id="{{ $c['id'] }}" aria-label="View {{ $c['title'] }} full size">
            <img src="{{ $c['photo'] }}" alt="{{ $c['title'] }}" loading="lazy" class="thumb card-img-top">
        </button>
    @elseif ($c['type'] === 'videos' && $c['video'])
        <video class="video-box" controls preload="none" playsinline data-type="videos" data-id="{{ $c['id'] }}" @if ($c['poster']) poster="{{ $c['poster'] }}" @endif><source src="{{ $c['video'] }}"></video>
    @elseif ($c['type'] === 'videos' && $c['embed'])
        <button type="button" class="embed-open js-embed" data-embed="{{ $c['embed'] }}" data-type="videos" data-id="{{ $c['id'] }}" aria-label="Play {{ $c['title'] }}">
            @if ($c['poster'])<img src="{{ $c['poster'] }}" alt="" loading="lazy">@endif
            <span class="play-badge"><i class="bi bi-play-fill"></i></span>
        </button>
    @else
        <a href="{{ $c['url'] }}" tabindex="-1" aria-hidden="true">
            @if ($c['image'])
                <img src="{{ $c['image'] }}" alt="{{ $c['title'] }}" loading="lazy" class="thumb card-img-top">
            @else
                <div class="thumb-fallback"><i class="bi {{ $c['icon'] }}"></i></div>
            @endif
        </a>
    @endif
    <div class="card-body d-flex flex-column">
        <div class="mb-2">
            @if ($c['category'])<span class="badge text-bg-success">{{ $c['category'] }}</span>@endif
            @if ($c['badge'])<span class="badge text-bg-{{ $c['badge'][1] }}">{{ $c['badge'][0] }}</span>@endif
        </div>
        <h3 class="h5"><a href="{{ $c['url'] }}" class="text-dark text-decoration-none">{{ $c['title'] }}</a></h3>
        @if ($c['summary'])<p class="text-muted small">{{ $c['summary'] }}</p>@endif
        @if ($c['meta'])<p class="small fw-semibold text-primary mb-2">{{ $c['meta'] }}</p>@endif
        <div class="mt-auto">
            @if (! in_array($c['type'], ['photos', 'videos'], true))
                <a href="{{ $c['url'] }}" class="btn btn-primary btn-sm mb-3">{{ $c['cta'] }}</a>
            @endif
            <div class="card-actions d-flex align-items-center gap-1 border-top pt-2">
                <span class="act-static" title="Views"><i class="bi bi-eye"></i> <span class="views-n">{{ number_format($c['views']) }}</span></span>
                <button type="button" class="act-btn js-like {{ $c['liked'] ? 'on' : '' }}" data-type="{{ $c['type'] }}" data-id="{{ $c['id'] }}" aria-pressed="{{ $c['liked'] ? 'true' : 'false' }}" aria-label="Like"><i class="bi {{ $c['liked'] ? 'bi-heart-fill' : 'bi-heart' }}"></i> <span class="n">{{ number_format($c['likes']) }}</span></button>
                <button type="button" class="act-btn js-comments" data-type="{{ $c['type'] }}" data-id="{{ $c['id'] }}" data-title="{{ $c['title'] }}" aria-label="Comments"><i class="bi bi-chat-dots"></i> <span class="n">{{ number_format($c['comments']) }}</span></button>
            </div>
        </div>
    </div>
</article>
