<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappDelivery extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['attempted_at' => 'datetime', 'accepted_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
