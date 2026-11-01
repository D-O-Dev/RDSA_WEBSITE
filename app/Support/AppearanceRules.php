<?php
namespace App\Support;

use Illuminate\Validation\Rule;

class AppearanceRules
{
    public static function layouts(): array
    {
        return array_values(array_unique(array_merge(['original', 'poster', 'split', 'editorial', 'panorama'], array_column(config('site_designs'), 'layout'))));
    }
    public static function rules(): array
    {
        return [
            'hero_background_path' => ['sometimes', 'nullable', 'string', 'max:2000', ImageSource::rule()],
            'hero_settings' => ['sometimes', 'array:mode,position,font,accent,interval,overlay,slides,design,foreground'],
            'hero_settings.design' => ['sometimes', 'array:preset,layout,effect,radius,motion,custom_colors,primary,accent,background'],
            'hero_settings.design.preset' => ['sometimes', Rule::in(array_keys(config('site_designs')))],
            'hero_settings.design.layout' => ['sometimes', Rule::in(array_merge(['auto'], self::layouts()))],
            'hero_settings.design.effect' => ['sometimes', Rule::in(['auto', 'solid', 'glass', 'frosted', 'liquid'])],
            'hero_settings.design.radius' => ['sometimes', Rule::in(['auto', 'soft', 'rounded', 'square'])],
            'hero_settings.design.motion' => ['sometimes', Rule::in(['gentle', 'expressive', 'none'])],
            'hero_settings.design.custom_colors' => ['sometimes', 'boolean'],
            'hero_settings.design.primary' => ['sometimes', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'hero_settings.design.accent' => ['sometimes', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'hero_settings.design.background' => ['sometimes', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'hero_settings.mode' => ['sometimes', Rule::in(['static', 'fade', 'slide', 'zoom'])],
            'hero_settings.position' => ['sometimes', Rule::in(['left', 'center', 'right'])],
            'hero_settings.font' => ['sometimes', Rule::in(['original', 'classic', 'modern'])],
            'hero_settings.accent' => ['sometimes', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'hero_settings.interval' => ['sometimes', 'integer', 'between:4,20'],
            'hero_settings.overlay' => ['sometimes', 'integer', 'between:20,85'],
            'hero_settings.slides' => ['sometimes', 'array', 'max:6'],
            'hero_settings.slides.*' => ['nullable', 'string', 'max:2000', ImageSource::rule()],
            'hero_settings.foreground' => ['sometimes', 'nullable', 'string', 'max:2000', ImageSource::rule()],
        ];
    }
}
