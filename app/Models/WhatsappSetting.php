<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WhatsappSetting extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['enabled' => 'boolean', 'access_token' => 'encrypted', 'app_secret' => 'encrypted', 'verify_token' => 'encrypted'];
    protected $hidden = ['access_token', 'app_secret', 'verify_token'];

    public static function current(): self
    {
        return static::find(1) ?? static::unguarded(fn () => static::firstOrCreate(['id' => 1], ['verify_token' => Str::random(48)]))->refresh();
    }
}
