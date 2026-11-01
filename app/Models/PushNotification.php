<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'expires_at' => 'datetime', 'attempted_at' => 'datetime'];
}
