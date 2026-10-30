<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

class SharedLink
{
    /** Resolve provider short links only on admin save, never during public page rendering. */
    public static function resolve(string $url, string $kind): string
    {
        $short = $kind === 'map' ? ['maps.app.goo.gl', 'goo.gl'] : ['vm.tiktok.com', 'vt.tiktok.com', 'fb.watch'];
        $allowed = $kind === 'map' ? [...$short, 'www.google.com', 'google.com', 'maps.google.com', 'www.google.fr', 'google.fr']
            : [...$short, 'www.tiktok.com', 'tiktok.com', 'm.tiktok.com', 'facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com'];
        $original = $url;
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if (!in_array($host, $short)) return $url;
        for ($i = 0; $i < 4; $i++) {
            $p = parse_url($url);
            if (!$p || ($p['scheme'] ?? '') !== 'https' || isset($p['port']) || isset($p['user']) || isset($p['pass']) || !in_array(strtolower($p['host'] ?? ''), $allowed)) return $original;
            if (!in_array(strtolower($p['host']), $short)) return $url;
            try {
                $response = Http::connectTimeout(2)->timeout(4)->withOptions(['allow_redirects' => false, 'stream' => true])->get($url);
                $next = $response->header('Location');
                $status = $response->status();
                $response->toPsrResponse()->getBody()->close();
                if ($status < 300 || $status >= 400 || !$next) return $original;
                if (str_starts_with($next, '/')) $next = 'https://' . $p['host'] . $next;
                $url = $next;
            } catch (Throwable) { return $original; }
        }
        return $original;
    }
}
