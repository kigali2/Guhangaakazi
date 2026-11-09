<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Category;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Skill;
use App\Models\Video;
use App\Support\Cards;
use App\Support\R2;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const TYPES = [
        'business-ideas' => [BusinessIdea::class, 'Business Idea', ['summary', 'description']],
        'skills' => [Skill::class, 'Skill', ['summary', 'description']],
        'opportunities' => [Opportunity::class, 'Opportunity', ['summary', 'description']],
        'articles' => [Article::class, 'Article', ['summary', 'content']],
        'photos' => [Photo::class, 'Photo', ['description']],
        'videos' => [Video::class, 'Video', ['description']],
    ];

    private const PLURALS = [
        'business-ideas' => 'Business Ideas',
        'skills' => 'Skills',
        'opportunities' => 'Opportunities',
        'articles' => 'Articles',
        'photos' => 'Photos',
        'videos' => 'Videos',
    ];

    private function term(Request $request): string
    {
        return mb_substr(trim((string) $request->query('q', '')), 0, 60);
    }

    private function find(string $type, string $term, int $limit)
    {
        [$model, , $columns] = self::TYPES[$type];
        $escaped = addcslashes($term, '%_\\');
        $like = '%' . $escaped . '%';
        $starts = $escaped . '%';

        return $model::published()
            ->with('category')
            ->where(function ($q) use ($like, $columns) {
                $q->where('title', 'like', $like);
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            })
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 WHEN title LIKE ? THEN 1 ELSE 2 END', [$starts, $like])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    public function suggest(Request $request)
    {
        $term = $this->term($request);
        if ($term === '') {
            return response()->json(['results' => []]);
        }

        $results = [];
        foreach (self::TYPES as $type => $def) {
            foreach ($this->find($type, $term, 5) as $m) {
                $key = match ($type) {
                    'photos' => $m->image_key,
                    'videos' => $m->thumbnail_key,
                    default => $m->cover_image_key,
                };
                $pos = mb_stripos($m->title, $term);
                $results[] = [
                    'type' => $type,
                    'label' => $def[1],
                    'title' => $m->title,
                    'sub' => $m->category?->name,
                    'url' => route('site.' . $type . '.show', $m->slug),
                    'image' => $key ? R2::url($key) : null,
                    'score' => $pos === 0 ? 0 : ($pos !== false ? 1 : 2),
                    'views' => (int) $m->views_count,
                ];
            }
        }

        $categories = Category::where('name', 'like', '%' . addcslashes($term, '%_\\') . '%')->orderBy('name')->limit(3)->get();
        foreach ($categories as $c) {
            $results[] = [
                'type' => 'category',
                'label' => 'Category',
                'title' => $c->name,
                'sub' => null,
                'url' => route('site.search', ['category' => $c->slug]),
                'image' => null,
                'score' => 1,
                'views' => 0,
            ];
        }

        usort($results, fn ($a, $b) => [$a['score'], -$a['views']] <=> [$b['score'], -$b['views']]);

        return response()->json(['results' => array_slice($results, 0, 14)]);
    }

    public function index(Request $request)
    {
        $term = $this->term($request);
        $categorySlug = (string) $request->query('category', '');
        $category = $categorySlug !== '' ? Category::where('slug', $categorySlug)->first() : null;

        $sections = [];
        $total = 0;

        if ($category || $term !== '') {
            foreach (self::TYPES as $type => $def) {
                if ($category) {
                    $rows = $def[0]::published()->with('category')->where('category_id', $category->id)->latest('published_at')->limit(24)->get();
                } else {
                    $rows = $this->find($type, $term, 24);
                }
                if ($rows->isEmpty()) {
                    continue;
                }
                $total += $rows->count();
                $sections[] = [
                    'title' => self::PLURALS[$type],
                    'cards' => $rows->map(fn ($m) => Cards::from($m, $type)),
                ];
            }
        }

        return view('public.search', [
            'term' => $category ? $category->name : $term,
            'sections' => $sections,
            'total' => $total,
        ]);
    }
}
