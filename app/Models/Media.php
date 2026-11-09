<?php

namespace App\Models;

use App\Support\R2;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';
    protected $guarded = [];

    public function getUrlAttribute(): string
    {
        return R2::url($this->object_key);
    }
}
