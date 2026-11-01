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
        'hero_background_path', 'hero_settings',
        'reservations_open_at', 'reservations_close_at', 'capacity',
    ];

    protected $casts = [
        'hero_settings' => 'array',
        'number' => 'integer',
        'starts_at' => 'datetime',
        'reservations_open_at' => 'datetime', 'reservations_close_at' => 'datetime', 'capacity' => 'integer',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }

    public function reservationsOpen(): bool
    {
        return $this->is_active && $this->is_published && ! $this->is_archived
            && (! $this->reservations_open_at || $this->reservations_open_at->lessThanOrEqualTo(now()))
            && (! $this->reservations_close_at || $this->reservations_close_at->isFuture())
            && (! $this->capacity || $this->participations()->whereIn('status', ['pending', 'confirmed'])->count() < $this->capacity);
    }
}
