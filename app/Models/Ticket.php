<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALID = 'valid';
    public const STATUS_USED = 'used';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'participation_id',
        'ticket_number',
        'reference',
        'qr_token_hash',
        'status',
        'issued_at',
    ];

    protected $casts = [
        'ticket_number' => 'integer',
        'issued_at' => 'datetime',
    ];

    // Ne jamais exposer qr_token_hash dans une réponse publique.
    protected $hidden = [
        'qr_token_hash',
    ];

    public function participation(): BelongsTo
    {
        return $this->belongsTo(Participation::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }
}