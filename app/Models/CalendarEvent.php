<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'starts_at', 'location', 'notes'];
    protected $casts = ['starts_at' => 'datetime'];
}
