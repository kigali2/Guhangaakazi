<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends Model
{
    use Publishable, SoftDeletes, \App\Models\Concerns\HasEngagement;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    public function category() { return $this->belongsTo(Category::class); }
    public function businessIdeas() { return $this->belongsToMany(BusinessIdea::class); }
    public function articles() { return $this->belongsToMany(Article::class); }
    public function photos() { return $this->belongsToMany(Photo::class); }
    public function videos() { return $this->belongsToMany(Video::class); }
}
