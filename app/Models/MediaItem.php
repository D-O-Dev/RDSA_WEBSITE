<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = ['is_published' => 'boolean', 'sort_order' => 'integer'];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_published', true)->where(fn ($q) => $q->whereNull('edition_id')
            ->orWhereHas('edition', fn ($e) => $e->where('is_published', true)));
    }

    public function sourceUrl(): ?string
    {
        if (!$this->file_path) return null;
        return $this->disk === 'public' ? asset('storage/' . $this->file_path) : asset($this->file_path);
    }

    public function embedUrl(): ?string
    {
        return \App\Support\MediaLink::parse($this->video_url)['embed'] ?? null;
    }

    public function publicData(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'kind' => $this->kind,
            'album' => $this->edition?->name ?? $this->album_name,
            'source' => $this->kind === 'video' && $this->video_url ? $this->video_url : $this->sourceUrl(),
            'thumbnail' => $this->kind === 'photo' ? $this->sourceUrl() : asset($this->cover_path ?: 'images/hero/rdsa3-banner.jpeg'),
            'embed' => $this->embedUrl(),
            'provider' => \App\Support\MediaLink::parse($this->video_url)['provider'] ?? null,
            'format' => ($this->display_format && $this->display_format !== 'auto') ? $this->display_format : (\App\Support\MediaLink::parse($this->video_url)['format'] ?? 'auto')];
    }
}
