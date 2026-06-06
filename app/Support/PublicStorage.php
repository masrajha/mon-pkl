<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicStorage
{
    public static function url(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            $path = self::pathFromPublicUrl($value);

            return $path ? route('media.public', ['path' => $path]) : $value;
        }

        if (Str::startsWith($value, '/storage/')) {
            return route('media.public', ['path' => Str::after($value, '/storage/')]);
        }

        if (Str::startsWith($value, 'storage/')) {
            return route('media.public', ['path' => Str::after($value, 'storage/')]);
        }

        if (! Str::startsWith($value, ['/'])) {
            return route('media.public', ['path' => $value]);
        }

        return $value;
    }

    public static function path(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return self::pathFromPublicUrl($value);
        }

        if (Str::startsWith($value, '/storage/')) {
            return Str::after($value, '/storage/');
        }

        if (Str::startsWith($value, 'storage/')) {
            return Str::after($value, 'storage/');
        }

        return Str::startsWith($value, '/') ? null : $value;
    }

    private static function pathFromPublicUrl(string $url): ?string
    {
        $storagePrefix = rtrim(Storage::disk('public')->url(''), '/').'/';

        if (Str::startsWith($url, $storagePrefix)) {
            return Str::after($url, $storagePrefix);
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && Str::startsWith($path, '/storage/')
            ? Str::after($path, '/storage/')
            : null;
    }
}
