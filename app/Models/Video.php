<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use Publishable, SoftDeletes, \App\Models\Concerns\HasEngagement;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime'];

    public function category() { return $this->belongsTo(Category::class); }
    public function businessIdeas() { return $this->belongsToMany(BusinessIdea::class); }
    public function skills() { return $this->belongsToMany(Skill::class); }
    public function articles() { return $this->belongsToMany(Article::class); }
    public function opportunities() { return $this->belongsToMany(Opportunity::class); }
}
