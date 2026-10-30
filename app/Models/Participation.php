<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participation extends Model
{
    use HasFactory;

    // Compatibility with historical rows: the retired financial columns are never exposed or charged.
    protected $attributes = ['unit_price' => 0, 'base_amount' => 0, 'extra_contribution' => 0, 'total_expected' => 0];
    protected $hidden = ['unit_price', 'base_amount', 'extra_contribution', 'total_expected', 'reservation_phone_hash'];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'participant_id',
        'edition_id',
        'reference',
        'submission_token',
        'status',
        'whatsapp_opt_in', 'reservation_phone_hash', 'has_attended_before', 'previous_edition_ids', 'reviewed_by', 'reviewed_at', 'review_reason',
    ];

    protected $casts = [
        'whatsapp_opt_in' => 'boolean', 'has_attended_before' => 'boolean', 'previous_edition_ids' => 'array', 'reviewed_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}