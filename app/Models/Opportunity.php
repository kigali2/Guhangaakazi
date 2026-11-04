<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use Publishable, SoftDeletes, \App\Models\Concerns\HasEngagement;

    protected $guarded = [];
    protected $casts = ['is_featured' => 'boolean', 'published_at' => 'datetime', 'opens_at' => 'date', 'closes_at' => 'date'];

    public function category() { return $this->belongsTo(Category::class); }
    public function photos() { return $this->belongsToMany(Photo::class); }
    public function videos() { return $this->belongsToMany(Video::class); }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status_override) {
            return $this->status_override;
        }
        $today = now()->startOfDay();
        if ($this->opens_at && $this->opens_at->gt($today)) {
            return 'upcoming';
        }
        if ($this->closes_at && $this->closes_at->lt($today)) {
            return 'closed';
        }
        if ($this->closes_at && $this->closes_at->lte($today->copy()->addDays(7))) {
            return 'closing_soon';
        }
        return 'open';
    }
}
