<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteUpdate extends Model
{
    use SoftDeletes;

    protected $fillable = ['kind', 'title', 'body', 'edition_id', 'is_published', 'sort_order', 'starts_at', 'ends_at'];
    protected $casts = ['is_published' => 'boolean', 'sort_order' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function scopeVisibleFor(Builder $query, ?Edition $edition): Builder
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->where(function ($q) use ($edition) {
                $q->whereNull('edition_id');
                if ($edition && $edition->is_active && $edition->is_published && !$edition->is_archived) $q->orWhere('edition_id', $edition->id);
            });
    }

    public function publicationLabel(): string
    {
        if (!$this->is_published) return 'Masqué';
        if ($this->ends_at && $this->ends_at->lessThanOrEqualTo(now())) return 'Terminé';
        if ($this->edition_id && (!$this->edition || !$this->edition->is_active || !$this->edition->is_published || $this->edition->is_archived)) return 'Édition inactive';
        if ($this->starts_at?->isFuture()) return 'Programmé';
        return 'Publié';
    }
}
