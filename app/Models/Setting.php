<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    private static ?array $all = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$all === null) {
            try {
                self::$all = static::query()->pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                self::$all = [];
            }
        }
        $value = self::$all[$key] ?? null;

        return ($value === null || $value === '') ? $default : (string) $value;
    }
}
