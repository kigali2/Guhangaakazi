<?php

namespace App\Support;

use App\Models\Like;
use Illuminate\Support\Str;

class Cards
{
    private const ICONS = [
        'business-ideas' => 'bi-lightbulb-fill',
        'skills' => 'bi-mortarboard-fill',
        'opportunities' => 'bi-rocket-takeoff-fill',
        'articles' => 'bi-journal-text',
        'photos' => 'bi-image-fill',
        'videos' => 'bi-play-circle-fill',
    ];

    private const CTA = [
        'business-ideas' => 'View Details',
        'skills' => 'Learn More',
        'opportunities' => 'View Opportunity',
        'articles' => 'Read Article',
        'photos' => 'View Photo',
        'videos' => 'Watch Video',
    ];

    private static ?array $liked = null;

    private static function isLiked($m): bool
    {
        if (self::$liked === null) {
            self::$liked = [];
            $token = request()->cookie('visitor_id');
            if (is_string($token) && strlen($token) === 40) {
                $rows = Like::where('visitor_hash', hash('sha256', $token))->get(['likeable_type', 'likeable_id']);
                foreach ($rows as $row) {
                    self::$liked[$row->likeable_type . '#' . $row->likeable_id] = true;
                }
            }
        }

        return isset(self::$liked[$m->getMorphClass() . '#' . $m->id]);
    }

    public static function embed(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^www\./', '', $parts['host']));
        $path = $parts['path'] ?? '';
        $id = null;

        if ($host === 'youtu.be') {
            $id = ltrim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'm.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            if (! empty($query['v'])) {
                $id = $query['v'];
            } elseif (preg_match('#^/(embed|shorts)/([A-Za-z0-9_-]{6,15})#', $path, $m)) {
                $id = $m[2];
            }
        } elseif ($host === 'vimeo.com' && preg_match('#^/([0-9]+)#', $path, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        if ($id !== null && preg_match('/^[A-Za-z0-9_-]{6,15}$/', (string) $id)) {
            return 'https://www.youtube-nocookie.com/embed/' . $id;
        }

        return null;
    }

    public static function statusBadge($m): ?array
    {
        if (! method_exists($m, 'getDisplayStatusAttribute')) {
            return null;
        }

        return match ($m->display_status) {
            'open' => ['Open', 'success'],
            'closing_soon' => ['Closing Soon', 'warning'],
            'closed' => ['Closed', 'secondary'],
            'upcoming' => ['Upcoming', 'info'],
            default => [ucfirst((string) $m->display_status), 'secondary'],
        };
    }

    public static function from($m, string $type): array
    {
        $key = match ($type) {
            'photos' => $m->image_key,
            'videos' => $m->thumbnail_key,
            default => \App\Support\Linked::featuredKey($m) ?: $m->cover_image_key,
        };

        $text = $m->summary ?: $m->description;

        $meta = null;
        if ($type === 'business-ideas' && $m->startup_capital !== null) {
            $meta = 'Startup capital: ' . ($m->currency ?: 'RWF') . ' ' . number_format((float) $m->startup_capital);
        } elseif ($type === 'opportunities' && $m->closes_at) {
            $meta = 'Closes: ' . $m->closes_at->format('d M Y');
        } elseif ($type === 'articles') {
            $meta = ($m->reading_time ?: 1) . ' min read';
        }

        $embed = $type === 'videos' ? self::embed($m->external_url) : null;
        $poster = ($type === 'videos' && $m->thumbnail_key) ? R2::url($m->thumbnail_key) : null;
        if (! $poster && $embed && str_contains($embed, 'youtube-nocookie.com/embed/')) {
            $poster = 'https://i.ytimg.com/vi/' . substr($embed, strrpos($embed, '/') + 1) . '/hqdefault.jpg';
        }

        return [
            'id' => $m->id,
            'type' => $type,
            'title' => $m->title,
            'url' => route('site.' . $type . '.show', $m->slug),
            'image' => $key ? R2::url($key) : null,
            'icon' => self::ICONS[$type] ?? 'bi-collection',
            'summary' => $text ? Str::limit(strip_tags((string) $text), 130) : null,
            'category' => $m->category?->name,
            'meta' => $meta,
            'badge' => $type === 'opportunities' ? self::statusBadge($m) : null,
            'cta' => self::CTA[$type] ?? 'View',
            'views' => (int) $m->views_count,
            'likes' => (int) $m->likes_count,
            'comments' => (int) $m->comments_count,
            'liked' => self::isLiked($m),
            'photo' => ($type === 'photos' && $key) ? R2::url($key) : null,
            'video' => ($type === 'videos' && $m->video_key) ? R2::url($m->video_key) : null,
            'embed' => $embed,
            'poster' => $poster,
        ];
    }
}
