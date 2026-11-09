<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    private const TYPES = [
        'jpg' => ['image', 'image/jpeg'],
        'jpeg' => ['image', 'image/jpeg'],
        'png' => ['image', 'image/png'],
        'webp' => ['image', 'image/webp'],
        'mp4' => ['video', 'video/mp4'],
        'webm' => ['video', 'video/webm'],
        'mov' => ['video', 'video/quicktime'],
    ];

    public function index(Request $request)
    {
        $query = Media::query()->latest();
        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where('original_name', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }
        if (in_array($request->query('type'), ['image', 'video'], true)) {
            $query->where('type', $request->query('type'));
        }

        return view('admin.media.index', ['items' => $query->paginate(24)->withQueryString()]);
    }

    public function link(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|string|max:600',
            'title' => 'nullable|string|max:200',
        ]);

        $key = R2::keyFromUrl($data['url']);
        if (! $key) {
            return back()->withInput()->withErrors([
                'url' => 'That link is not from your R2 public URL (' . config('r2.url') . '). Check R2_PUBLIC_URL in .env.',
            ]);
        }

        $info = self::TYPES[strtolower(pathinfo($key, PATHINFO_EXTENSION))] ?? null;
        if (! $info) {
            return back()->withInput()->withErrors(['url' => 'Unsupported file type. Use JPG, PNG, WebP, MP4, WebM or MOV.']);
        }

        $size = 0;
        try {
            $size = (int) R2::disk()->size($key);
        } catch (\Throwable $e) {
            // size is optional
        }

        $title = trim((string) ($data['title'] ?? ''));
        Media::firstOrCreate(
            ['object_key' => $key],
            [
                'disk' => 'r2',
                'original_name' => Str::limit(strip_tags($title !== '' ? $title : basename($key)), 250, ''),
                'mime_type' => $info[1],
                'type' => $info[0],
                'size' => $size,
                'uploaded_by' => $request->user()->id,
            ]
        );

        return back()->with('success', 'File added to the library.');
    }

    private function inUse(string $key): bool
    {
        $places = [
            ['business_ideas', 'cover_image_key'], ['skills', 'cover_image_key'],
            ['opportunities', 'cover_image_key'], ['articles', 'cover_image_key'],
            ['photos', 'image_key'], ['videos', 'video_key'], ['videos', 'thumbnail_key'],
            ['homepage_slides', 'image_key'], ['categories', 'image_key'],
        ];
        foreach ($places as $p) {
            if (DB::table($p[0])->where($p[1], $key)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function destroy(Request $request, Media $medium)
    {
        if ($this->inUse($medium->object_key)) {
            return back()->with('error', 'This file is used by website content. Replace it there first.');
        }

        if ($request->boolean('remove_from_r2')) {
            try {
                R2::disk()->delete($medium->object_key);
            } catch (\Throwable $e) {
                report($e);
                return back()->with('error', 'Could not delete the file from R2.');
            }
        }

        $medium->delete();

        return back()->with('success', 'Removed from the library.');
    }
}
