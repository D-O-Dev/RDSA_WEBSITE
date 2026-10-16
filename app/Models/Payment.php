<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'participation_id',
        'wave_number_id',
        'transaction_reference',
        'amount_declared',
        'payment_date_declared',
        'wave_phone_snapshot',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount_declared' => 'integer',
        'payment_date_declared' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function participation(): BelongsTo
    {
        return $this->belongsTo(Participation::class);
    }

    public function waveNumber(): BelongsTo
    {
        return $this->belongsTo(WaveNumber::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }
}