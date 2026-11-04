<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use Publishable, SoftDeletes, \App\Models\Concerns\HasEngagement;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (Article $a) {
            $words = str_word_count(strip_tags((string) $a->content));
            $a->reading_time = max(1, (int) ceil($words / 200));
        });
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function businessIdeas() { return $this->belongsToMany(BusinessIdea::class); }
    public function skills() { return $this->belongsToMany(Skill::class); }
    public function photos() { return $this->belongsToMany(Photo::class); }
    public function videos() { return $this->belongsToMany(Video::class); }
}
