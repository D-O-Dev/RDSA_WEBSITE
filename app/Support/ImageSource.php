<?php

namespace App\Support;

class ImageSource
{
    /** Images are displayed by the browser; the server never fetches an arbitrary URL. */
    public static function valid(mixed $value): bool
    {
        if (!is_string($value) || strlen($value) > 2000 || preg_match('/[<>"\'\\\\\x00-\x1F]/', $value)) return false;
        if (str_starts_with($value, 'https://')) {
            $url = parse_url($value);
            $host = strtolower($url['host'] ?? '');
            if (!filter_var($value, FILTER_VALIDATE_URL) || isset($url['user']) || isset($url['pass'])
                || isset($url['fragment']) || (isset($url['port']) && $url['port'] !== 443)) return false;
            if (filter_var($host, FILTER_VALIDATE_IP)) return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if (!str_contains($host, '.') || preg_match('/(?:^|\.)(localhost|local|test|internal|invalid|example)$/', $host)) return false;
            // A social post/player is not a direct image address.
            if (preg_match('/(?:^|\.)(youtube\.com|youtu\.be|facebook\.com|tiktok\.com|instagram\.com)$/', $host)) return false;
            return !preg_match('/\.(?:html?|php|svg)(?:$|\?)/i', $url['path'] ?? '');
        }
        if (!str_starts_with($value, 'images/') || str_contains($value, '..')) return false;
        $root = realpath(public_path('images')); $path = realpath(public_path($value));
        return $root && $path && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)
            && in_array(mime_content_type($path), ['image/jpeg', 'image/png', 'image/webp']);
    }

    public static function rule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (!self::valid($value)) $fail('Utilise un lien HTTPS public direct vers une image JPG, PNG ou WebP, ou choisis une image locale existante.');
        };
    }
}
