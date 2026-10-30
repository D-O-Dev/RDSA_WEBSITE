<?php

namespace App\Support;

class EditionLocation
{
    public static function validUrl(?string $url): bool
    {
        $p = parse_url($url ?? '');
        if (!$p || ($p['scheme'] ?? '') !== 'https' || isset($p['user']) || isset($p['pass']) || isset($p['port'])) return false;
        $host = strtolower($p['host'] ?? '');
        return (in_array($host, ['google.com', 'www.google.com', 'maps.google.com', 'google.fr', 'www.google.fr']) && str_starts_with($p['path'] ?? '', '/maps'))
            || $host === 'maps.google.com'
            || ($host === 'maps.app.goo.gl' && strlen($p['path'] ?? '') > 1)
            || ($host === 'goo.gl' && str_starts_with($p['path'] ?? '', '/maps/'));
    }

    public static function coordinates(?string $url): ?array
    {
        if (!self::validUrl($url)) return null;
        $decoded = rawurldecode($url);
        // Place marker coordinates, never the @lat,lng camera position.
        if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $decoded, $m)) return self::pair($m[1], $m[2]);
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        foreach (['q', 'query', 'destination', 'll'] as $key) {
            if (isset($query[$key]) && is_string($query[$key]) && preg_match('/^(?:loc:)?(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)$/', $query[$key], $m)) return self::pair($m[1], $m[2]);
        }
        return null;
    }

    private static function pair($lat, $lng): ?array
    {
        return abs((float) $lat) <= 90 && abs((float) $lng) <= 180 ? [(float) $lat, (float) $lng] : null;
    }

    public static function data($edition): ?array
    {
        if (!$edition) return null;
        $coords = $edition->latitude !== null && $edition->longitude !== null
            ? [(float) $edition->latitude, (float) $edition->longitude] : self::coordinates($edition->map_url);
        if ($coords) {
            $position = implode(',', $coords);
            return ['embed' => 'https://maps.google.com/maps?q=' . rawurlencode($position) . '&z=16&output=embed&hl=fr',
                'directions' => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($position), 'precise' => true];
        }
        if ($edition->map_url && self::validUrl($edition->map_url)) {
            $p = parse_url($edition->map_url);
            parse_str($p['query'] ?? '', $q);
            if (str_contains($p['path'] ?? '', '/maps/embed') && isset($q['pb']) && is_string($q['pb'])) {
                return ['embed' => 'https://www.google.com/maps/embed?pb=' . rawurlencode($q['pb']) . '&hl=fr', 'directions' => $edition->map_url, 'precise' => true];
            }
            foreach (['q', 'query', 'destination'] as $key) {
                if (!empty($q[$key]) && is_string($q[$key])) {
                    return ['embed' => 'https://maps.google.com/maps?q=' . rawurlencode($q[$key]) . '&output=embed&hl=fr', 'directions' => $edition->map_url, 'precise' => true];
                }
            }
            if (!empty($q['cid']) && is_string($q['cid']) && ctype_digit($q['cid'])) {
                return ['embed' => 'https://maps.google.com/maps?cid=' . $q['cid'] . '&output=embed&hl=fr', 'directions' => $edition->map_url, 'precise' => true];
            }
            // Google place identifiers can be embedded without substituting a guessed address.
            if (preg_match('/!1s([^!?#]+)/', $edition->map_url, $match)) {
                $place = rawurldecode($match[1]);
                if (preg_match('/^0x[a-f0-9]+:0x([a-f0-9]+)$/i', $place, $id)) {
                    $hex = str_pad($id[1], 16, '0', STR_PAD_LEFT);
                    // Google CIDs may exceed PHP's signed integer range.
                    $decimal = '0';
                    foreach (str_split($hex) as $digit) {
                        $carry = hexdec($digit); $next = '';
                        for ($i = strlen($decimal) - 1; $i >= 0; $i--) { $n = (int) $decimal[$i] * 16 + $carry; $next = ($n % 10) . $next; $carry = intdiv($n, 10); }
                        $decimal = ($carry ? (string) $carry : '') . $next;
                    }
                    return ['embed' => 'https://maps.google.com/maps?cid=' . $decimal . '&output=embed&hl=fr', 'directions' => $edition->map_url, 'precise' => true];
                }
                if (preg_match('/^ChI[a-zA-Z0-9_-]+$/', $place)) return ['embed' => 'https://maps.google.com/maps?q=place_id:' . rawurlencode($place) . '&output=embed&hl=fr', 'directions' => $edition->map_url, 'precise' => true];
            }
            // A short link cannot be used inside an iframe. Do not substitute an unrelated address.
            return ['embed' => null, 'directions' => $edition->map_url, 'precise' => false];
        }
        if (!$edition->map_url && trim($edition->venue ?? '') !== '') {
            $address = trim($edition->venue . ' ' . ($edition->venue_details ?? ''));
            return ['embed' => 'https://maps.google.com/maps?q=' . rawurlencode($address) . '&z=16&output=embed&hl=fr',
                'directions' => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($address), 'precise' => false];
        }
        return null;
    }
}
