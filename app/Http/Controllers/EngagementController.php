<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Skill;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EngagementController extends Controller
{
    private const TYPES = [
        'business-ideas' => BusinessIdea::class,
        'skills' => Skill::class,
        'opportunities' => Opportunity::class,
        'articles' => Article::class,
        'photos' => Photo::class,
        'videos' => Video::class,
    ];

    private function item(string $type, int $id)
    {
        $class = self::TYPES[$type] ?? abort(404);
        return $class::published()->findOrFail($id);
    }

    private function visitor(Request $request): string
    {
        $token = $request->cookie('visitor_id');
        if (! is_string($token) || strlen($token) !== 40) {
            $token = Str::random(40);
            Cookie::queue(cookie('visitor_id', $token, 60 * 24 * 365));
        }
        return hash('sha256', $token);
    }

    public function like(Request $request, string $type, int $id)
    {
        $item = $this->item($type, $id);
        $hash = $this->visitor($request);
        $liked = false;

        DB::transaction(function () use ($item, $hash, &$liked) {
            $existing = $item->likes()->where('visitor_hash', $hash)->first();
            if ($existing) {
                $existing->delete();
                $item->decrement('likes_count');
            } else {
                $item->likes()->create(['visitor_hash' => $hash]);
                $item->increment('likes_count');
                $liked = true;
            }
        });

        return response()->json(['liked' => $liked, 'likes' => $item->fresh()->likes_count]);
    }

    public function view(Request $request, string $type, int $id)
    {
        $item = $this->item($type, $id);
        $inserted = DB::table('content_views')->insertOrIgnore([
            'viewable_type' => $item->getMorphClass(),
            'viewable_id' => $item->id,
            'visitor_hash' => $this->visitor($request),
            'viewed_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($inserted) {
            $item->increment('views_count');
        }
        return response()->json(['views' => $item->fresh()->views_count]);
    }

    public function comment(Request $request, string $type, int $id)
    {
        $item = $this->item($type, $id);
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'email' => 'nullable|email|max:150',
            'body' => 'required|string|min:2|max:1500',
            'parent_id' => 'nullable|integer',
            'website' => 'prohibited',
        ]);

        if (! empty($data['parent_id'])) {
            $item->comments()->whereNull('parent_id')->findOrFail($data['parent_id']);
        }

        $comment = $item->comments()->create([
            'name' => strip_tags($data['name'] ?? '') ?: 'Visitor',
            'email' => $data['email'] ?? null,
            'body' => strip_tags($data['body']),
            'parent_id' => $data['parent_id'] ?? null,
            'status' => 'approved',
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['ok' => true, 'id' => $comment->id, 'comments' => $item->fresh()->comments_count], 201);
    }
}
