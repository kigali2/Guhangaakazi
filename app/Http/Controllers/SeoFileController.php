<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Skill;
use App\Models\Video;

class SeoFileController extends Controller
{
    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /engage/',
            'Disallow: /search',
            'Allow: /',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        $entries = [];
        $add = function (string $loc, $lastmod, string $freq, string $priority) use (&$entries) {
            $entries[] = [$loc, $lastmod, $freq, $priority];
        };

        $add(url('/'), now()->toAtomString(), 'daily', '1.0');
        foreach (['business-ideas', 'skills', 'opportunities', 'articles', 'photos', 'videos'] as $type) {
            $add(route('site.' . $type . '.index'), now()->toAtomString(), 'daily', '0.9');
        }
        foreach (['about', 'mission', 'vision', 'contact', 'privacy', 'terms'] as $page) {
            $add(route('site.' . $page), null, 'monthly', '0.5');
        }

        $models = [
            'business-ideas' => BusinessIdea::class,
            'skills' => Skill::class,
            'opportunities' => Opportunity::class,
            'articles' => Article::class,
            'photos' => Photo::class,
            'videos' => Video::class,
        ];
        foreach ($models as $type => $model) {
            $model::published()->select(['id', 'slug', 'updated_at'])->orderBy('id')->chunk(500, function ($rows) use ($type, $add) {
                foreach ($rows as $row) {
                    $add(route('site.' . $type . '.show', $row->slug), optional($row->updated_at)->toAtomString(), 'weekly', '0.7');
                }
            });
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as [$loc, $lastmod, $freq, $priority]) {
            $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if ($lastmod) {
                $xml .= '<lastmod>' . $lastmod . '</lastmod>';
            }
            $xml .= '<changefreq>' . $freq . '</changefreq><priority>' . $priority . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
