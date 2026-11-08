<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GalleryController extends Controller
{
    private function cfg(string $res): array
    {
        $cfg = config('gallery.' . $res);
        abort_unless(is_array($cfg), 404);

        return $cfg;
    }

    public function index(Request $request, string $res)
    {
        $cfg = $this->cfg($res);
        $query = $cfg['model']::query()->with('category')->latest();

        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }
        if (in_array($request->query('status'), ['draft', 'published'], true)) {
            $query->where('status', $request->query('status'));
        }

        return view('admin.content.index', [
            'res' => $res,
            'cfg' => $cfg,
            'items' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create(string $res)
    {
        $cfg = $this->cfg($res);

        return view('admin.gallery.form', [
            'res' => $res,
            'cfg' => $cfg,
            'item' => new $cfg['model'](),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function edit(string $id, string $res)
    {
        $cfg = $this->cfg($res);

        return view('admin.gallery.form', [
            'res' => $res,
            'cfg' => $cfg,
            'item' => $cfg['model']::findOrFail($id),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $res)
    {
        $item = $this->persist($request, $res, null);

        return redirect()->route('admin.' . $res . '.edit', $item->id)->with('success', 'Saved.');
    }

    public function update(Request $request, string $id, string $res)
    {
        $cfg = $this->cfg($res);
        $item = $cfg['model']::findOrFail($id);
        $this->persist($request, $res, $item);

        return redirect()->route('admin.' . $res . '.edit', $item->id)->with('success', 'Saved.');
    }

    public function destroy(string $id, string $res)
    {
        $cfg = $this->cfg($res);
        $cfg['model']::findOrFail($id)->delete();

        return redirect()->route('admin.' . $res . '.index')->with('success', 'Deleted.');
    }

    public function publish(string $id, string $res)
    {
        $cfg = $this->cfg($res);
        $item = $cfg['model']::findOrFail($id);

        if ($item->status === 'published') {
            $item->status = 'draft';
        } else {
            $item->status = 'published';
            if (! $item->published_at) {
                $item->published_at = now();
            }
        }
        $item->save();

        return back()->with('success', $item->status === 'published' ? 'Published.' : 'Unpublished (now a draft).');
    }

    public function feature(string $id, string $res)
    {
        $cfg = $this->cfg($res);
        $item = $cfg['model']::findOrFail($id);
        $item->is_featured = ! $item->is_featured;
        $item->save();

        return back()->with('success', $item->is_featured ? 'Marked as featured.' : 'Removed from featured.');
    }

    private function persist(Request $request, string $res, $item)
    {
        $cfg = $this->cfg($res);

        $rules = [
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($cfg['table'], 'slug')->ignore($item?->id)],
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:20000',
            'status' => 'required|in:draft,published',
            'is_featured' => 'nullable|boolean',
        ];
        foreach ($cfg['links'] as $link) {
            $rules[$link[0]] = ($link[3] ? 'required' : 'nullable') . '|string|max:600';
        }
        foreach ($cfg['extra'] as $extra) {
            $rules[$extra[0]] = $extra[3];
        }

        $data = $request->validate($rules);
        $errors = [];

        foreach ($cfg['links'] as $link) {
            $value = $data[$link[0]] ?? null;
            unset($data[$link[0]]);
            $key = null;
            if ($value) {
                $key = R2::keyFromUrl($value);
                if (! $key) {
                    $errors[$link[0]] = 'Paste a link from your R2 public URL (' . config('r2.url') . ').';
                }
            }
            $data[$link[1]] = $key;
        }

        if ($res === 'videos') {
            $external = $data['external_url'] ?? null;
            $data['provider'] = null;
            if (empty($data['video_key']) && ! $external && ! isset($errors['video'])) {
                $errors['video'] = 'Add a video link from R2, or a YouTube / Vimeo link.';
            }
            if ($external) {
                $host = strtolower(preg_replace('/^www\./', '', (string) parse_url($external, PHP_URL_HOST)));
                if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtu.be'], true)) {
                    $data['provider'] = 'youtube';
                } elseif ($host === 'vimeo.com') {
                    $data['provider'] = 'vimeo';
                } else {
                    $errors['external_url'] = 'Only YouTube and Vimeo links are allowed.';
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $slug = ! empty($data['slug']) ? $data['slug'] : Str::slug($data['title']);
        if ($slug === '') {
            $slug = 'item-' . Str::lower(Str::random(6));
        }
        if (empty($data['slug'])) {
            $model = $cfg['model'];
            $candidate = $slug;
            $n = 2;
            while ($model::withTrashed()->where('slug', $candidate)->when($item?->id, fn ($q) => $q->where('id', '!=', $item->id))->exists()) {
                $candidate = $slug . '-' . $n;
                $n++;
            }
            $slug = $candidate;
        }
        $data['slug'] = $slug;
        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['status'] === 'published' && ! ($item && $item->published_at)) {
            $data['published_at'] = now();
        }

        if ($item === null) {
            $item = new $cfg['model']();
        }
        $item->fill($data);
        $item->save();

        return $item;
    }
}
