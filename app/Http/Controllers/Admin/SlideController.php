<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSlide;
use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SlideController extends Controller
{
    public function index()
    {
        return view('admin.slides.index', [
            'slides' => HomepageSlide::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.slides.form', ['slide' => new HomepageSlide()]);
    }

    public function edit(HomepageSlide $slide)
    {
        return view('admin.slides.form', ['slide' => $slide]);
    }

    public function store(Request $request)
    {
        $this->save($request, new HomepageSlide());

        return redirect()->route('admin.slides.index')->with('success', 'Slide added.');
    }

    public function update(Request $request, HomepageSlide $slide)
    {
        $this->save($request, $slide);

        return redirect()->route('admin.slides.index')->with('success', 'Slide updated.');
    }

    public function destroy(HomepageSlide $slide)
    {
        $slide->delete();

        return back()->with('success', 'Slide deleted.');
    }

    public function toggle(HomepageSlide $slide)
    {
        $slide->is_active = ! $slide->is_active;
        $slide->save();

        return back()->with('success', $slide->is_active ? 'Slide activated.' : 'Slide deactivated.');
    }

    public function move(HomepageSlide $slide, string $direction)
    {
        $order = HomepageSlide::orderBy('sort_order')->orderBy('id')->pluck('id')->all();
        $i = array_search($slide->id, $order, true);
        if ($i === false) {
            return back();
        }
        $j = $direction === 'up' ? $i - 1 : $i + 1;
        if (! isset($order[$j])) {
            return back();
        }
        [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
        foreach ($order as $position => $id) {
            HomepageSlide::where('id', $id)->update(['sort_order' => $position + 1]);
        }

        return back();
    }

    private function save(Request $request, HomepageSlide $slide): void
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'button_label' => 'nullable|string|max:60',
            'button_link' => ['nullable', 'string', 'max:500', 'regex:#^(/|https?://)#i'],
            'image' => 'required|string|max:600',
            'alt_text' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $key = R2::keyFromUrl($data['image']);
        if (! $key) {
            throw ValidationException::withMessages([
                'image' => 'Paste a link from your R2 public URL (' . config('r2.url') . ').',
            ]);
        }

        $slide->fill([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'button_label' => $data['button_label'] ?? null,
            'button_link' => $data['button_link'] ?? null,
            'image_key' => $key,
            'alt_text' => $data['alt_text'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! $slide->exists) {
            $slide->sort_order = (int) HomepageSlide::max('sort_order') + 1;
        }

        $slide->save();
    }
}
