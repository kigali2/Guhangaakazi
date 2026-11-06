<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Redirect;
use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    private function cfg(string $res): array
    {
        $cfg = config('cms.' . $res);
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

        $status = $request->query('status');
        if (in_array($status, ['draft', 'published'], true)) {
            $query->where('status', $status);
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

        return view('admin.content.form', [
            'res' => $res,
            'cfg' => $cfg,
            'item' => new $cfg['model'](),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function edit(string $id, string $res)
    {
        $cfg = $this->cfg($res);

        return view('admin.content.form', [
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

    private function rules(array $cfg, ?int $id): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($cfg['table'], 'slug')->ignore($id)],
            'category_id' => 'nullable|exists:categories,id',
            'summary' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|string|max:600',
            'cover_image_alt' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:320',
            'focus_keyword' => 'nullable|string|max:255',
        ];

        foreach ($cfg['fields'] as $field) {
            $rules[$field[0]] = $field[3];
        }

        return $rules;
    }

    private function uniqueSlug(array $cfg, string $base, ?int $id): string
    {
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'item-' . Str::lower(Str::random(6));
        }

        $model = $cfg['model'];
        $candidate = $slug;
        $n = 2;
        while ($model::withTrashed()->where('slug', $candidate)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            $candidate = $slug . '-' . $n;
            $n++;
        }

        return $candidate;
    }

    private function persist(Request $request, string $res, $item)
    {
        $cfg = $this->cfg($res);
        $data = $request->validate($this->rules($cfg, $item?->id));

        $cover = null;
        if (! empty($data['cover_image'])) {
            $cover = R2::keyFromUrl($data['cover_image']);
            if (! $cover) {
                throw ValidationException::withMessages([
                    'cover_image' => 'Paste a link from your R2 public URL (' . config('r2.url') . ').',
                ]);
            }
        }
        unset($data['cover_image']);
        $data['cover_image_key'] = $cover;

        $data['slug'] = ! empty($data['slug'])
            ? $data['slug']
            : $this->uniqueSlug($cfg, $data['title'], $item?->id);

        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (array_key_exists('currency', $data) && empty($data['currency'])) {
            $data['currency'] = 'RWF';
        } elseif (! empty($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        $oldSlug = $item?->slug;

        if ($item === null) {
            $item = new $cfg['model']();
            if ($res === 'articles') {
                $data['author_id'] = $request->user()->id;
            }
        }

        $item->fill($data);
        $item->save();

        if ($oldSlug && $oldSlug !== $item->slug) {
            $prefix = '/' . $cfg['public'] . '/';
            Redirect::where('from_path', $prefix . $item->slug)->delete();
            Redirect::updateOrCreate(
                ['from_path' => $prefix . $oldSlug],
                ['to_path' => $prefix . $item->slug]
            );
        }

        return $item;
    }
}
