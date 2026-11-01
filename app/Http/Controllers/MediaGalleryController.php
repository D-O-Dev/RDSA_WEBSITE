<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaGalleryController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['kind' => ['nullable', Rule::in(['all', 'photo', 'video'])], 'album' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1', 'max:10000']]);
        $items = MediaItem::with('edition')->visible()
            ->when(in_array($data['kind'] ?? '', ['photo', 'video']), fn ($q) => $q->where('kind', $data['kind']))
            ->when($data['album'] ?? null, fn ($q, $album) => $q->where(fn ($q) => $q->whereHas('edition', fn ($e) => $e->where('name', $album))->orWhere(fn ($q) => $q->whereNull('edition_id')->where('album_name', $album))))
            ->orderBy('sort_order')->orderByDesc('id')->paginate(60);

        return response()->json(['items' => $items->getCollection()->map(fn ($item) => $item->publicData())->values(), 'total' => $items->total(), 'page' => $items->currentPage(), 'last_page' => $items->lastPage()])->header('Cache-Control', 'no-store');
    }
}
