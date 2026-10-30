<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'values'];
    protected $casts = ['values' => 'array'];

    public static function fields(): array
    {
        return collect(config('site_content'))->pluck('fields')->collapse()->all();
    }

    public static function current(): array
    {
        $defaults = collect(static::fields())->map(fn ($field) => $field['default'])->all();
        $saved = static::find('public')?->values ?? [];
        foreach (['about', 'editions', 'media', 'contact'] as $section) {
            if (!isset($saved[$section . '_heading']) && isset($saved[$section . '_title'], $saved[$section . '_title_accent'])) {
                $saved[$section . '_heading'] = trim($saved[$section . '_title'] . ' ' . $saved[$section . '_title_accent']);
            }
        }
        return array_replace($defaults, array_intersect_key($saved, $defaults));
    }

    public static function heading(string $title): array
    {
        $words = preg_split('/\s+/u', trim($title));
        $accent = array_pop($words);
        return [implode(' ', $words), $accent];
    }
}
