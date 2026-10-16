<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone',
    ];

    public function participations(): HasMany
    {
        return $this->hasMany(Participation::class);
    }
}