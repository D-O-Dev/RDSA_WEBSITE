<?php

namespace App\Support;

use App\Models\MediaItem;

class PublicMedia
{
    public static function overview(): array
    {
        return [
            'mediaItems' => MediaItem::with('edition')->visible()->orderBy('sort_order')->orderByDesc('id')->limit(60)->get()->map(fn ($item) => $item->publicData())->values(),
            'mediaTotals' => MediaItem::visible()->selectRaw('kind, COUNT(*) AS total')->groupBy('kind')->pluck('total', 'kind'),
            'mediaAlbums' => MediaItem::with('edition')->visible()->select(['edition_id', 'album_name'])->distinct()->get()->map(fn ($item) => $item->edition?->name ?? $item->album_name)->unique()->filter()->values(),
        ];
    }
}
