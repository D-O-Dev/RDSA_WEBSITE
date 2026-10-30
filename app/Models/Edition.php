<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    use HasFactory;

    protected $attributes = ['base_price' => 0];
    protected $hidden = ['base_price', 'hero_landscape_path', 'hero_portrait_path'];

    protected $fillable = [
        'name',
        'slug',
        'number',
        'theme',
        'description',
        'starts_at',
        'venue',
        'venue_details',
        'map_url', 'latitude', 'longitude', 'contact_phone', 'contact_whatsapp',
        'is_active',
        'is_published',
        'is_archived',
        'hero_background_path',
    ];

    protected $casts = [
        'number' => 'integer',
        'starts_at' => 'datetime',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }
}