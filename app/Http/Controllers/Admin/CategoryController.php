<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'items' => Category::orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request)
    {
        $this->save($request, new Category());

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', ['item' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $this->save($request, $category);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        foreach (['businessIdeas', 'skills', 'opportunities', 'articles', 'photos', 'videos'] as $relation) {
            if ($category->{$relation}()->withTrashed()->exists()) {
                return back()->with('error', 'This category is still used by content. Move that content to another category first.');
            }
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function save(Request $request, Category $category): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:600',
        ]);

        $image = null;
        if (! empty($data['image'])) {
            $image = R2::keyFromUrl($data['image']);
            if (! $image) {
                throw ValidationException::withMessages([
                    'image' => 'Paste a link from your R2 public URL (' . config('r2.url') . ').',
                ]);
            }
        }

        $slug = Str::slug($data['name']);
        if ($slug === '') {
            $slug = 'category-' . Str::lower(Str::random(6));
        }
        $candidate = $slug;
        $n = 2;
        while (Category::where('slug', $candidate)->where('id', '!=', $category->id ?? 0)->exists()) {
            $candidate = $slug . '-' . $n;
            $n++;
        }

        $category->fill([
            'name' => strip_tags($data['name']),
            'slug' => $candidate,
            'description' => $data['description'] ?? null,
            'image_key' => $image,
        ]);
        $category->save();
    }
}
