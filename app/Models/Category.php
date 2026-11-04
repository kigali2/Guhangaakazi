<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    public function businessIdeas() { return $this->hasMany(BusinessIdea::class); }
    public function skills() { return $this->hasMany(Skill::class); }
    public function opportunities() { return $this->hasMany(Opportunity::class); }
    public function articles() { return $this->hasMany(Article::class); }
    public function photos() { return $this->hasMany(Photo::class); }
    public function videos() { return $this->hasMany(Video::class); }
}
