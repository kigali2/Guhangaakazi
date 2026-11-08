<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Category;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Redirect;
use App\Models\Skill;
use App\Models\Video;
use App\Support\Cards;
use App\Support\R2;
use Illuminate\Http\Request;

class SiteContentController extends Controller
{
    private const TYPES = [
        'business-ideas' => [BusinessIdea::class, 'Business Ideas', 'Explore practical business ideas with clear guidance on how to start.'],
        'skills' => [Skill::class, 'Skills', 'Learn practical skills you can use to work, earn and grow.'],
        'opportunities' => [Opportunity::class, 'Opportunities', 'Discover business, employment and education opportunities.'],
        'articles' => [Article::class, 'Articles', 'Read educational articles that support your growth.'],
        'photos' => [Photo::class, 'Photos', 'Browse our photo gallery.'],
        'videos' => [Video::class, 'Videos', 'Watch our video gallery.'],
    ];

    private const RELATIONS = [
        'business-ideas' => ['skills' => 'skills', 'articles' => 'articles', 'photos' => 'photos', 'videos' => 'videos'],
        'skills' => ['businessIdeas' => 'business-ideas', 'articles' => 'articles', 'photos' => 'photos', 'videos' => 'videos'],
        'opportunities' => ['photos' => 'photos', 'videos' => 'videos'],
        'articles' => ['businessIdeas' => 'business-ideas', 'skills' => 'skills', 'photos' => 'photos', 'videos' => 'videos'],
        'photos' => ['businessIdeas' => 'business-ideas', 'skills' => 'skills', 'articles' => 'articles', 'opportunities' => 'opportunities'],
        'videos' => ['businessIdeas' => 'business-ideas', 'skills' => 'skills', 'articles' => 'articles', 'opportunities' => 'opportunities'],
    ];

    private function type(string $res): array
    {
        abort_unless(isset(self::TYPES[$res]), 404);

        return self::TYPES[$res];
    }

    public function index(Request $request, string $res)
    {
        [$model, $label, $intro] = $this->type($res);

        $query = $model::published()->with('category');

        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $column = in_array($res, ['photos', 'videos'], true) ? 'description' : 'summary';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere($column, 'like', $like));
        }

        $category = $request->query('category');
        if ($category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $sort = $request->query('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('published_at');
        } elseif ($sort === 'views') {
            $query->orderByDesc('views_count');
        } elseif ($sort === 'likes') {
            $query->orderByDesc('likes_count');
        } elseif ($sort === 'title') {
            $query->orderBy('title');
        } else {
            $query->orderByDesc('published_at');
        }

        $items = $query->paginate(12)->withQueryString()->through(fn ($m) => Cards::from($m, $res));

        $categories = Category::whereIn('id', $model::published()->whereNotNull('category_id')->select('category_id'))
            ->orderBy('name')
            ->get();

        return view('public.list', [
            'res' => $res,
            'label' => $label,
            'intro' => $intro,
            'items' => $items,
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, string $slug, string $res)
    {
        [$model, $label] = $this->type($res);

        $item = $model::published()->with('category')->where('slug', $slug)->first();
        if (! $item) {
            $redirect = Redirect::where('from_path', '/' . $res . '/' . $slug)->first();
            if ($redirect) {
                return redirect($redirect->to_path, 301);
            }
            abort(404);
        }

        $titles = [
            'business-ideas' => 'Related Business Ideas',
            'skills' => 'Related Skills',
            'opportunities' => 'Related Opportunities',
            'articles' => 'Related Articles',
            'photos' => 'Related Photos',
            'videos' => 'Related Videos',
        ];

        $related = [];
        foreach (self::RELATIONS[$res] as $relation => $relType) {
            $rows = $item->{$relation}()->published()->with('category')->limit(6)->get();
            if ($rows->isNotEmpty()) {
                $related[] = [
                    'title' => $titles[$relType],
                    'cards' => $rows->map(fn ($m) => Cards::from($m, $relType)),
                ];
            }
        }

        $similar = $model::published()
            ->with('category')
            ->where('id', '!=', $item->id)
            ->when($item->category_id, fn ($q) => $q->where('category_id', $item->category_id))
            ->latest('published_at')
            ->take(3)
            ->get()
            ->map(fn ($m) => Cards::from($m, $res));

        $token = $request->cookie('visitor_id');
        $liked = is_string($token) && strlen($token) === 40
            && $item->likes()->where('visitor_hash', hash('sha256', $token))->exists();

        $data = [
            'res' => $res,
            'label' => $label,
            'cfg' => config('cms.' . $res),
            'item' => $item,
            'related' => $related,
            'similar' => $similar,
            'liked' => $liked,
            'comments' => $item->approvedComments()->get(),
            'status' => Cards::statusBadge($item),
        ];

        if ($res === 'photos') {
            $data['imageUrl'] = R2::url($item->image_key);
        }
        if ($res === 'videos') {
            $data['videoUrl'] = $item->video_key ? R2::url($item->video_key) : null;
            $data['poster'] = $item->thumbnail_key ? R2::url($item->thumbnail_key) : null;
            $data['embed'] = $this->embed($item->external_url);
        }

        return view(in_array($res, ['photos', 'videos'], true) ? 'public.media' : 'public.show', $data);
    }

    private function embed(?string $url): ?string
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
}
