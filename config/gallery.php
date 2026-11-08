<?php

use App\Models\Photo;
use App\Models\Video;

return [
    'photos' => [
        'model' => Photo::class,
        'table' => 'photos',
        'label' => 'Photos',
        'singular' => 'Photo',
        'links' => [
            ['image', 'image_key', 'Photo link (from your R2 bucket)', true],
        ],
        'extra' => [
            ['alt_text', 'Photo description (alt text)', 'text', 'nullable|string|max:255'],
        ],
    ],
    'videos' => [
        'model' => Video::class,
        'table' => 'videos',
        'label' => 'Videos',
        'singular' => 'Video',
        'links' => [
            ['video', 'video_key', 'Video link (from your R2 bucket)', false],
            ['thumbnail', 'thumbnail_key', 'Thumbnail image link (optional)', false],
        ],
        'extra' => [
            ['external_url', 'Or a YouTube / Vimeo link', 'url', 'nullable|url:http,https|max:500'],
        ],
    ],
];
