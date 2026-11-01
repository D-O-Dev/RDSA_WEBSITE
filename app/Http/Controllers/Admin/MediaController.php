<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'kind' => ['nullable', Rule::in(['photo', 'video'])], 'publication' => ['nullable', Rule::in(['published', 'hidden'])]]);
        $items = MediaItem::with('edition')->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($m) => $m->where('title', 'like', '%' . $s . '%')->orWhere('album_name', 'like', '%' . $s . '%')->orWhereHas('edition', fn ($e) => $e->where('name', 'like', '%' . $s . '%'))))
            ->when($filters['kind'] ?? null, fn ($q, $k) => $q->where('kind', $k))
            ->when($filters['publication'] ?? null, fn ($q, $p) => $q->where('is_published', $p === 'published'))
            ->orderBy('sort_order')->orderByDesc('id')->paginate(20)->withQueryString();
        $editing = $request->filled('edit') ? MediaItem::findOrFail($request->integer('edit'))
            : ($request->boolean('create') ? new MediaItem(['kind' => ($filters['kind'] ?? 'photo'), 'album_name' => 'RDSA', 'sort_order' => 0, 'is_published' => false]) : null);
        $files = collect(File::allFiles(public_path('images')))->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))
            ->map(fn ($f) => 'images/' . $f->getRelativePathname())->sort()->values();
        $bytes = static function ($value) { $value = trim($value); $unit = strtolower(substr($value, -1)); return (int) ((float) $value * match ($unit) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 }); };
        $uploadLimit = min($bytes(ini_get('upload_max_filesize')) ?: 52428800, ($bytes(ini_get('post_max_size')) ?: 62914560) - 65536);
        return view('admin.media.index', ['items' => $items, 'editing' => $editing, 'uploadLimit' => $uploadLimit, 'files' => $files, 'editions' => Edition::orderByDesc('starts_at')->get()]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new MediaItem);
    }

    public function update(Request $request, MediaItem $mediaItem)
    {
        return $this->save($request, $mediaItem);
    }

    public function publish(Request $request, MediaItem $mediaItem)
    {
        $data = $request->validate(['is_published' => ['required', 'boolean']]);
        $mediaItem->update($data);
        $filters = [];
        if ($request->header('X-RDSA-Navigation') === '1') {
            $query = parse_url($request->headers->get('referer', ''), PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
                $filters = array_intersect_key($params, array_flip(['q', 'kind', 'publication', 'page']));
            }
        }
        return redirect()->route('admin.media.index', $filters)->with('status', 'Publication du média mise à jour.');
    }

    public function destroy(MediaItem $mediaItem)
    {
        // Le fichier peut être partagé par un autre contenu : on retire seulement le média.
        $mediaItem->delete();
        return redirect()->route('admin.media.index')->with('status', 'Média retiré du site et de la médiathèque.');
    }

    private function asset(string $path, array $extensions): bool
    {
        $root = realpath(public_path('images'));
        $full = !str_contains($path, '..') && !str_contains($path, "\0") ? realpath(public_path($path)) : false;
        if (!$full || !$root || !str_starts_with($full, $root . DIRECTORY_SEPARATOR) || !is_file($full)) return false;
        if (!in_array(strtolower(pathinfo($full, PATHINFO_EXTENSION)), $extensions)) return false;
        return in_array(strtolower(pathinfo($full, PATHINFO_EXTENSION)), ['mp4', 'webm']) || @getimagesize($full) !== false;
    }

    private function save(Request $request, MediaItem $item)
    {
        $usingLink = $request->filled('video_url') && !$request->hasFile('upload');
        if ($usingLink) $request->merge(['kind' => 'video']);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'], 'kind' => ['required', Rule::in(['photo', 'video'])],
            'edition_id' => ['nullable', 'integer', 'exists:editions,id'], 'album_name' => ['nullable', 'required_without:edition_id', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'], 'is_published' => ['required', 'boolean'],
            'existing_path' => ['nullable', 'string', 'max:500'], 'cover_path' => ['nullable', 'string', 'max:2000', \App\Support\ImageSource::rule()],
            'image_url' => ['nullable', 'string', 'max:2000', 'starts_with:https://', \App\Support\ImageSource::rule()],
            'video_url' => ['required_if:kind,video', 'nullable', 'url:https', 'max:1000'],
            'display_format' => ['nullable', Rule::in(['auto', 'portrait', 'landscape', 'square'])],
            'upload' => $request->input('kind') === 'photo' ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'] : ['prohibited'],
        ], [
            'title.required' => 'Indique un titre pour ce média.', 'title.max' => 'Le titre est limité à 150 caractères.',
            'album_name.required_without' => 'Choisis une édition ou renseigne le nom de l’album.',
            'upload.image' => 'Choisis une image JPG, PNG ou WebP valide.',
            'upload.mimes' => 'Utilise une photo JPG, PNG ou WebP.',
            'upload.prohibited' => 'Les vidéos sont ajoutées uniquement par lien YouTube, Facebook, TikTok, Vimeo ou Instagram.',
            'video_url.required_if' => 'Colle le lien de la vidéo hébergée sur ta plateforme.',
            'upload.max' => 'Le fichier dépasse la taille autorisée.', 'video_url.url' => 'Indique un lien HTTPS valide.',
        ]);
        $sameKind = $item->exists && $item->kind === $data['kind'];
        $path = $sameKind ? $item->file_path : null;
        $disk = $sameKind ? $item->disk : 'assets';
        if (!$usingLink && !empty($data['existing_path'])) {
            $extensions = $data['kind'] === 'photo' ? ['jpg', 'jpeg', 'png', 'webp'] : ['mp4', 'webm'];
            if (!$this->asset($data['existing_path'], $extensions)) throw ValidationException::withMessages(['existing_path' => 'Ce fichier n’est pas disponible ou ne correspond pas au type choisi.']);
            $path = $data['existing_path']; $disk = 'assets';
        }

        $url = $usingLink ? $data['video_url'] : null;
        if ($url) $url = \App\Support\SharedLink::resolve($url, 'video');
        if ($url && !(new MediaItem(['video_url' => $url]))->embedUrl()) {
            throw ValidationException::withMessages(['video_url' => 'Utilise le lien complet d’une vidéo YouTube, Facebook, TikTok, Vimeo ou Instagram. Pour un lien raccourci, ouvre la vidéo puis copie son adresse complète.']);
        }
        $imageUrl = $data['kind'] === 'photo' ? ($data['image_url'] ?? null) : null;
        if ($imageUrl && !$request->hasFile('upload')) { $path = $imageUrl; $disk = 'external'; }
        if (!$path && !$request->hasFile('upload') && !$url) {
            throw ValidationException::withMessages(['upload' => 'Ajoute une photo ou sélectionne une image existante. Pour une vidéo, utilise un lien compatible.']);
        }
        $stored = null;
        try {
            if ($request->hasFile('upload')) {
                $stored = $request->file('upload')->store('rdsa/media', 'public');
                if (!$stored) throw new \RuntimeException('Échec de stockage du média.');
                $path = $stored; $disk = 'public'; $url = null;
            } elseif ($url) { $path = null; $disk = 'assets'; }
            $item->fill(collect($data)->except(['existing_path', 'upload', 'video_url', 'image_url'])->all());
            $item->display_format = $data['display_format'] ?? ($item->display_format ?: 'auto');
            $item->album_name = ($data['album_name'] ?? '') ?: 'RDSA';
            $item->file_path = $path; $item->disk = $disk; $item->video_url = $url;
            $item->save();
            $request->attributes->set('admin_action_target', 'Média #' . $item->id);
        } catch (Throwable $exception) {
            if ($stored) Storage::disk('public')->delete($stored);
            throw $exception;
        }
        return redirect()->route('admin.media.index')->with('status', 'Média enregistré.');
    }
}
