<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaLink;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LinkController extends Controller
{
    private const ROLES = [
        'featured' => ['Featured image', 'image'],
        'gallery' => ['Gallery image', 'image'],
        'video' => ['Related video', 'video'],
        'document' => ['Document (PDF)', 'document'],
    ];

    private function types(): array
    {
        $types = [];
        foreach (['business-ideas', 'skills', 'opportunities', 'articles'] as $key) {
            $types[$key] = config('cms.' . $key);
        }

        return $types;
    }

    public function index(Request $request)
    {
        $types = $this->types();
        $query = MediaLink::with(['media', 'linkable'])->latest();
        $type = $request->query('type');
        if ($type && isset($types[$type])) {
            $query->where('linkable_type', $types[$type]['model']);
        }

        return view('admin.links.index', [
            'links' => $query->paginate(20)->withQueryString(),
            'types' => $types,
            'roles' => self::ROLES,
        ]);
    }

    public function create()
    {
        $items = [];
        foreach ($this->types() as $key => $cfg) {
            $items[$key] = $cfg['model']::orderBy('title')->get(['id', 'title'])
                ->map(fn ($m) => ['id' => $m->id, 'title' => $m->title])->all();
        }
        $media = Media::latest()->limit(150)->get()
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->original_name, 'type' => $m->type, 'url' => $m->url])->all();

        return view('admin.links.form', [
            'types' => $this->types(),
            'roles' => self::ROLES,
            'payload' => ['items' => $items, 'media' => $media, 'roleTypes' => collect(self::ROLES)->map(fn ($r) => $r[1])->all()],
        ]);
    }

    public function store(Request $request)
    {
        $types = $this->types();
        $data = $request->validate([
            'content_type' => ['required', Rule::in(array_keys($types))],
            'content_id' => 'required|integer',
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'media_ids' => 'required|array|min:1|max:30',
            'media_ids.*' => 'integer|exists:media,id',
            'caption' => 'nullable|string|max:255',
        ]);

        $content = $types[$data['content_type']]['model']::find($data['content_id']);
        if (! $content) {
            throw ValidationException::withMessages(['content_id' => 'Choose a content item.']);
        }
        if ($data['role'] === 'featured' && count($data['media_ids']) > 1) {
            throw ValidationException::withMessages(['media_ids' => 'Choose only one image for a featured image.']);
        }

        $needed = self::ROLES[$data['role']][1];
        $files = Media::whereIn('id', $data['media_ids'])->get();
        foreach ($files as $file) {
            if ($file->type !== $needed) {
                throw ValidationException::withMessages(['media_ids' => 'The role "' . self::ROLES[$data['role']][0] . '" needs a ' . $needed . ' file.']);
            }
        }

        $type = $content->getMorphClass();
        if ($data['role'] === 'featured') {
            MediaLink::where('linkable_type', $type)->where('linkable_id', $content->id)->where('role', 'featured')->delete();
        }

        $order = (int) MediaLink::where('linkable_type', $type)->where('linkable_id', $content->id)->max('sort_order');
        foreach ($files as $file) {
            MediaLink::updateOrCreate(
                ['media_id' => $file->id, 'linkable_type' => $type, 'linkable_id' => $content->id, 'role' => $data['role']],
                ['caption' => $data['caption'] ?? null, 'sort_order' => ++$order]
            );
        }

        return redirect()->route('admin.links.index')->with('success', count($files) . ' link(s) saved.');
    }

    public function edit(MediaLink $link)
    {
        $link->load(['media', 'linkable']);
        $roles = array_filter(self::ROLES, fn ($r) => $r[1] === $link->media->type);

        return view('admin.links.edit', ['link' => $link, 'roles' => $roles]);
    }

    public function update(Request $request, MediaLink $link)
    {
        $link->load('media');
        $allowed = array_keys(array_filter(self::ROLES, fn ($r) => $r[1] === $link->media->type));
        $data = $request->validate([
            'role' => ['required', Rule::in($allowed)],
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:100000',
        ]);

        $duplicate = MediaLink::where('media_id', $link->media_id)->where('linkable_type', $link->linkable_type)
            ->where('linkable_id', $link->linkable_id)->where('role', $data['role'])->where('id', '!=', $link->id)->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['role' => 'This file already has that role for this content.']);
        }
        if ($data['role'] === 'featured') {
            MediaLink::where('linkable_type', $link->linkable_type)->where('linkable_id', $link->linkable_id)
                ->where('role', 'featured')->where('id', '!=', $link->id)->delete();
        }

        $link->update([
            'role' => $data['role'],
            'caption' => $data['caption'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? $link->sort_order),
        ]);

        return redirect()->route('admin.links.index')->with('success', 'Link updated.');
    }

    public function destroy(MediaLink $link)
    {
        $link->delete();

        return back()->with('success', 'Link removed. The file is still in the Media Library.');
    }
}
