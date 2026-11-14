<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use App\Models\Like;
use App\Models\MediaLink;

trait HasEngagement
{
    public function likes() { return $this->morphMany(Like::class, 'likeable'); }

    public function comments() { return $this->morphMany(Comment::class, 'commentable'); }

    public function approvedComments()
    {
        return $this->comments()->where('status', 'approved')->whereNull('parent_id')->with('replies')->latest();
    }

    public function mediaLinks()
    {
        return $this->morphMany(MediaLink::class, 'linkable')->with('media')->orderBy('sort_order')->orderBy('id');
    }
}
