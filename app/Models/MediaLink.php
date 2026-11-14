<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLink extends Model
{
    protected $guarded = [];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function linkable()
    {
        return $this->morphTo();
    }
}
