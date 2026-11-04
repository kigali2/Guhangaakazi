<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $guarded = [];
    protected $casts = ['is_reported' => 'boolean'];

    protected static function booted(): void
    {
        $sync = function (Comment $c) {
            $parent = $c->commentable;
            if ($parent) {
                $parent->forceFill([
                    'comments_count' => $parent->comments()->where('status', 'approved')->count(),
                ])->saveQuietly();
            }
        };
        static::saved($sync);
        static::deleted($sync);
    }

    public function commentable() { return $this->morphTo(); }
    public function parent() { return $this->belongsTo(Comment::class, 'parent_id'); }
    public function replies() { return $this->hasMany(Comment::class, 'parent_id')->where('status', 'approved')->oldest(); }
}
