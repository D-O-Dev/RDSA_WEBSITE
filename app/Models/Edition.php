<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'number',
        'theme',
        'description',
        'starts_at',
        'venue',
        'venue_details',
        'base_price',
        'is_active',
    ];

    protected $casts = [
        'number' => 'integer',
        'starts_at' => 'datetime',
        'base_price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }
}