<?php

namespace App\Models\Concerns;

trait Publishable
{
    public function scopePublished($q)
    {
        return $q->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }
}
