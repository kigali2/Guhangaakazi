<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class R2
{
    public static function disk()
    {
        return Storage::build([
            'driver' => 's3',
            'key' => config('r2.key'),
            'secret' => config('r2.secret'),
            'region' => 'auto',
            'bucket' => config('r2.bucket'),
            'endpoint' => config('r2.endpoint'),
            'use_path_style_endpoint' => true,
            'throw' => true,
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
        ]);
    }

    public static function url(?string $key): string
    {
        if (! $key) {
            return '';
        }
        $segments = array_map('rawurlencode', explode('/', $key));

        return rtrim((string) config('r2.url'), '/') . '/' . implode('/', $segments);
    }

    public static function keyFromUrl(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $input)) {
            $base = rtrim((string) config('r2.url'), '/');
            if ($base === '' || ! str_starts_with($input, $base . '/')) {
                return null;
            }
            $rest = substr($input, strlen($base) + 1);
            $rest = strtok($rest, '?#');
        } else {
            $rest = ltrim($input, '/');
        }

        if ($rest === false || $rest === '' || $rest === null) {
            return null;
        }

        $key = rawurldecode($rest);
        if (str_contains($key, '..') || str_contains($key, "\0") || strlen($key) > 500) {
            return null;
        }

        return $key;
    }
}
