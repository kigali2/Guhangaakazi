<?php

namespace App\Support;

class Linked
{
    public static function featuredKey($m): ?string
    {
        if (! method_exists($m, 'mediaLinks')) {
            return null;
        }
        $link = $m->mediaLinks()->where('role', 'featured')->first();

        return $link?->media?->object_key;
    }

    public static function coverUrl($m): ?string
    {
        $key = self::featuredKey($m) ?: $m->cover_image_key;

        return $key ? R2::url($key) : null;
    }
}
