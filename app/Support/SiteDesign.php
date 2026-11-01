<?php

namespace App\Support;

use App\Models\Edition;

class SiteDesign
{
    public static function resolve(?Edition $edition): array
    {
        $settings = $edition?->hero_settings['design'] ?? [];
        $catalog = config('site_designs');
        $preset = isset($catalog[$settings['preset'] ?? '']) ? $settings['preset'] : 'original';
        $design = $catalog[$preset];
        foreach (['layout' => AppearanceRules::layouts(), 'effect' => ['solid', 'glass', 'frosted', 'liquid'], 'radius' => ['soft', 'rounded', 'square']] as $key => $allowed) {
            if (in_array($settings[$key] ?? '', $allowed, true)) $design[$key] = $settings[$key];
        }
        if ($settings['custom_colors'] ?? false) foreach (['primary', 'accent', 'background'] as $key) {
            if (preg_match('/^#[a-fA-F0-9]{6}$/', $settings[$key] ?? '')) $design[$key] = $settings[$key];
        }
        $design['template'] = in_array($design['layout'], ['aerial', 'atelier', 'horizon', 'sanctuary', 'harvest', 'cloud', 'lilac', 'immersion', 'voyage'], true) ? $design['layout'] : null;
        $design['preset'] = $preset;
        $design['active'] = $preset !== 'original' || ($settings['custom_colors'] ?? false) || in_array($settings['effect'] ?? '', ['solid', 'glass', 'frosted', 'liquid'], true) || in_array($settings['radius'] ?? '', ['soft', 'rounded', 'square'], true);
        $design['motion'] = in_array($settings['motion'] ?? '', ['gentle', 'expressive', 'none'], true) ? $settings['motion'] : 'gentle';
        $design['text'] = self::ink($design['background']);
        $dark = $design['text'] === '#f5f7fa';
        if ($settings['custom_colors'] ?? false) $design['surface'] = $dark ? '#1d2732' : '#ffffff';
        $design['muted'] = $dark ? '#bbc8ce' : '#52616b';
        $design['on_primary'] = self::ink($design['primary']);
        $design['on_accent'] = self::ink($design['accent']);
        $design['accent_photo'] = self::contrast($design['accent'], '#07152f') >= 4.5 ? $design['accent'] : '#f5f7fa';
        $design['accent_text'] = self::contrast($design['accent'], $design['background']) >= 4.5 ? $design['accent'] : $design['text'];
        $design['edge'] = $dark ? 'rgba(255,255,255,.20)' : 'rgba(20,35,45,.18)';
        $alpha = ['solid' => 1, 'glass' => .78, 'frosted' => .92, 'liquid' => .65][$design['effect']];
        $hex = ltrim($design['surface'], '#');
        $design['panel'] = 'rgba(' . implode(',', [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $alpha]) . ')';
        $properties = ['primary', 'accent', 'background', 'surface', 'text', 'muted', 'on_primary', 'on_accent', 'edge', 'panel', 'accent_text', 'accent_photo'];
        $design['style'] = implode(';', array_map(fn ($key) => '--design-' . str_replace('_', '-', $key) . ':' . $design[$key], $properties));
        return $design;
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function ($offset) use ($hex) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;
            return $value <= .04045 ? $value / 12.92 : (($value + .055) / 1.055) ** 2.4;
        }, [1, 3, 5]);
        return $channels[0] * .2126 + $channels[1] * .7152 + $channels[2] * .0722;
    }
    private static function contrast(string $a, string $b): float
    {
        $first = self::luminance($a); $second = self::luminance($b);
        return (max($first, $second) + .05) / (min($first, $second) + .05);
    }
    private static function ink(string $hex): string
    {
        return self::contrast($hex, '#f5f7fa') > self::contrast($hex, '#17212b') ? '#f5f7fa' : '#17212b';
    }
}
