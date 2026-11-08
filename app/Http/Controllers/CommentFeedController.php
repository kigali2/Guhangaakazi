<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Skill;
use App\Models\Video;

class CommentFeedController extends Controller
{
    private const TYPES = [
        'business-ideas' => BusinessIdea::class,
        'skills' => Skill::class,
        'opportunities' => Opportunity::class,
        'articles' => Article::class,
        'photos' => Photo::class,
        'videos' => Video::class,
    ];

    public function index(string $type, string $id)
    {
        $class = self::TYPES[$type] ?? abort(404);
        $item = $class::published()->findOrFail($id);

        $rows = $item->approvedComments()->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'body' => $c->body,
            'when' => $c->created_at->diffForHumans(),
            'replies' => $c->replies->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'body' => $r->body,
                'when' => $r->created_at->diffForHumans(),
            ])->values(),
        ])->values();

        return response()->json(['comments' => $rows]);
    }
}
