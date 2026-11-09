<?php

namespace App\Support;

use Illuminate\Support\Str;

class Seo
{
    public static function schemas($item, $res, $poster, $videoUrl, $embed): array
    {
        $schemas = [];

        if (request()->routeIs('site.home')) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => config('site.name'),
                'url' => url('/'),
                'sameAs' => [
                    'https://www.instagram.com/novasouleric25',
                    'https://www.tiktok.com/@jean.eric14',
                    'https://www.facebook.com/jean.eric.882106',
                    'https://x.com/eric1242907',
                    'https://www.youtube.com/@Tech-website-system',
                ],
            ];
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('site.name'),
                'url' => url('/'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/search') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ];
        }

        if ($item && $res === 'articles') {
            $schemas[] = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => Str::limit((string) $item->title, 110, ''),
                'description' => Str::limit(strip_tags((string) ($item->meta_description ?: $item->summary)), 200),
                'image' => $item->cover_image_key ? [R2::url($item->cover_image_key)] : null,
                'datePublished' => optional($item->published_at)->toAtomString(),
                'dateModified' => optional($item->updated_at)->toAtomString(),
                'author' => $item->author ? ['@type' => 'Person', 'name' => $item->author->name] : null,
                'mainEntityOfPage' => url()->current(),
            ]);
        }

        if ($item && $res === 'videos' && $poster) {
            $schemas[] = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $item->title,
                'description' => Str::limit(strip_tags((string) ($item->description ?: $item->title)), 300),
                'thumbnailUrl' => [$poster],
                'uploadDate' => optional($item->published_at ?? $item->created_at)->toAtomString(),
                'contentUrl' => $videoUrl,
                'embedUrl' => $embed,
            ]);
        }

        return $schemas;
    }
}
