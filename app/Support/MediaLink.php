<?php

namespace App\Support;

/** Only canonical, trusted player URLs are ever used as iframe sources. */
class MediaLink
{
    public static function parse(?string $url): ?array
    {
        $parts = parse_url($url ?? '');
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return null;
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '/';
        parse_str($parts['query'] ?? '', $query);
        $id = null; $portrait = false;
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be'])) {
            if ($host === 'youtu.be') $id = trim($path, '/');
            elseif ($path === '/watch') $id = $query['v'] ?? null;
            elseif (preg_match('~^/(shorts|live|embed)/([\w-]{11})/?$~', $path, $m)) { $id = $m[2]; $portrait = $m[1] === 'shorts'; }
            if (!is_string($id) || !preg_match('/^[\w-]{11}$/', $id)) return null;
            return ['provider' => 'YouTube', 'embed' => "https://www.youtube-nocookie.com/embed/$id?playsinline=1&rel=0", 'format' => $portrait ? 'portrait' : 'landscape'];
        }
        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com']) && preg_match('~^/(?:video/)?(\d+)(?:/([a-zA-Z0-9]+))?/?$~', $path, $m)) {
            $hash = $m[2] ?? ($query['h'] ?? null);
            $suffix = is_string($hash) && ctype_alnum($hash) ? '?h=' . $hash : '';
            return ['provider' => 'Vimeo', 'embed' => 'https://player.vimeo.com/video/' . $m[1] . $suffix, 'format' => 'landscape'];
        }
        if (in_array($host, ['www.tiktok.com', 'tiktok.com', 'm.tiktok.com']) && preg_match('~^/(?:@[^/]+/video/|player/v1/)(\d+)/?$~', $path, $m)) {
            return ['provider' => 'TikTok', 'embed' => 'https://www.tiktok.com/player/v1/' . $m[1] . '?autoplay=0&loop=0', 'format' => 'portrait'];
        }
        if (in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com'])) {
            $isVideo = preg_match('~^/(?:[^/]+/videos/(?:[^/]+/)?\d+|reel/\d+|share/(?:v|r)/[a-zA-Z0-9]+|watch)/?$~', $path);
            if (!$isVideo || ($path === '/watch' || $path === '/watch/') && (!isset($query['v']) || !is_string($query['v']) || !ctype_digit($query['v']))) return null;
            $canonical = 'https://www.facebook.com' . $path . (isset($query['v']) && is_string($query['v']) ? '?v=' . rawurlencode($query['v']) : '');
            return ['provider' => 'Facebook', 'embed' => 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($canonical) . '&show_text=false&width=1280', 'format' => str_contains($path, '/reel/') || str_contains($path, '/share/r/') ? 'portrait' : 'landscape'];
        }
        if (in_array($host, ['instagram.com', 'www.instagram.com']) && preg_match('~^/(reel|p|tv)/([a-zA-Z0-9_-]+)/?$~', $path, $m)) {
            return ['provider' => 'Instagram', 'embed' => 'https://www.instagram.com/' . $m[1] . '/' . $m[2] . '/embed/', 'format' => $m[1] === 'p' ? 'square' : 'portrait'];
        }
        return null;
    }
}
