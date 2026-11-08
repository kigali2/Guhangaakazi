<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\HomepageSlide;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\Video;
use App\Support\Cards;
use App\Support\R2;

class PageController extends Controller
{
    private const TITLES = [
        'about' => 'About Us',
        'mission' => 'Our Mission',
        'vision' => 'Our Vision',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Use',
    ];

    public function home()
    {
        $limit = max(1, min(12, (int) Setting::get('featured_limit', '6')));

        $slides = HomepageSlide::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($s) {
                $link = $s->button_link;
                if ($link && ! preg_match('#^(/|https?://)#i', $link)) {
                    $link = null;
                }

                return [
                    'image' => R2::url($s->image_key),
                    'alt' => $s->alt_text ?: (string) $s->title,
                    'title' => $s->title,
                    'description' => $s->description,
                    'button' => ($s->button_label && $link) ? [$s->button_label, $link] : null,
                ];
            });

        $defs = [
            'business-ideas' => [BusinessIdea::class, 'Business Ideas', 'View all business ideas'],
            'skills' => [Skill::class, 'Skills', 'View all skills'],
            'opportunities' => [Opportunity::class, 'Opportunities', 'View all opportunities'],
            'articles' => [Article::class, 'Articles', 'View all articles'],
            'photos' => [Photo::class, 'Photos', 'Open the photo gallery'],
            'videos' => [Video::class, 'Videos', 'Open the video gallery'],
        ];

        $sections = [];
        foreach ($defs as $type => [$model, $title, $linkLabel]) {
            $items = $model::published()->featured()->with('category')->latest('published_at')->take($limit)->get();
            if ($items->isEmpty()) {
                $items = $model::published()->with('category')->latest('published_at')->take($limit)->get();
            }
            if ($items->isEmpty()) {
                continue;
            }
            $sections[] = [
                'title' => $title,
                'link' => route('site.' . $type . '.index'),
                'linkLabel' => $linkLabel,
                'cards' => $items->map(fn ($m) => Cards::from($m, $type)),
            ];
        }

        return view('public.home', [
            'slides' => $slides,
            'heading' => Setting::get('hero_heading', config('site.tagline')),
            'intro' => Setting::get('hero_description', config('site.description')),
            'sections' => $sections,
        ]);
    }

    public function text(string $key)
    {
        abort_unless(isset(self::TITLES[$key]), 404);

        return view('public.text', [
            'key' => $key,
            'title' => self::TITLES[$key],
            'content' => Setting::get($key . '_content', config('site.pages.' . $key)),
        ]);
    }

    public function contact()
    {
        return view('public.contact', ['contacts' => config('site.contacts')]);
    }
}
