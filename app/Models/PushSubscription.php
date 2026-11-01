<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['subscription' => 'encrypted:array'];

    protected $hidden = ['subscription', 'delete_token_hash'];
}
