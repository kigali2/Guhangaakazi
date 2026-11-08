<?php

namespace App\Models\Concerns;

trait Publishable
{
    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }
}
