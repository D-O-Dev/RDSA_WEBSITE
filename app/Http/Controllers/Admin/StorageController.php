<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\MediaItem;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StorageController extends Controller
{
    private function unused(): array
    {
        $used = [];
        $visit = function ($value) use (&$visit, &$used) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $visit($v);
                }

return;
            }
            if (! is_string($value)) {
                return;
            }
            if (str_starts_with($value, 'storage/rdsa/')) {
                $used[substr($value, 8)] = true;
            }
            if (str_starts_with($value, 'rdsa/')) {
                $used[$value] = true;
            }
        };
        foreach (Edition::select(['hero_background_path', 'hero_settings'])->cursor() as $e) {
            $visit([$e->hero_background_path, $e->hero_settings]);
        }
        foreach (SiteContent::cursor() as $c) {
            $visit($c->values);
        }
        foreach (MediaItem::withTrashed()->select(['file_path', 'cover_path'])->cursor() as $m) {
            $visit([$m->file_path, $m->cover_path]);
        }
        $disk = Storage::disk('public');
        $files = [];
        foreach (['rdsa/media', 'rdsa/visuals'] as $directory) {
            foreach ($disk->files($directory) as $path) {
                if (isset($used[$path]) || $disk->lastModified($path) > now()->subDay()->timestamp || is_link($disk->path($path))) {
                    continue;
                }
                $files[] = ['path' => $path, 'size' => $disk->size($path)];
            }
        }

        return $files;
    }

    public function index()
    {
        return view('admin.storage', ['files' => $this->unused()]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password:web'], 'paths' => ['required', 'array', 'max:100'], 'paths.*' => ['required', 'string', 'distinct']]);
        $allowed = array_column($this->unused(), 'path');
        foreach ($data['paths'] as $path) {
            if (! in_array($path, $allowed, true)) {
                throw ValidationException::withMessages(['paths' => 'Un fichier sélectionné est encore utilisé, récent ou indisponible.']);
            }
        }
        foreach ($data['paths'] as $path) {
            Storage::disk('public')->delete($path);
        }
        $request->attributes->set('admin_action_target', count($data['paths']).' fichiers inutilisés');

        return redirect()->route('admin.storage.index')->with('status', 'Fichiers inutilisés sélectionnés supprimés.');
    }

    public function uploadPhotos(Request $request)
    {
        $data = $request->validate(['photos' => ['required', 'array', 'min:1', 'max:20'], 'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'album_name' => ['required', 'string', 'max:100'], 'edition_id' => ['nullable', 'integer', 'exists:editions,id']]);
        $stored = [];
        try {
            DB::transaction(function () use ($data, &$stored) {
                foreach ($data['photos'] as $index => $photo) {
                    $path = $photo->store('rdsa/media', 'public');
                    if (! $path) {
                        throw new \RuntimeException('Échec du téléversement.');
                    }
                    $stored[] = $path;
                    MediaItem::create(['title' => $data['album_name'].' — '.($index + 1), 'kind' => 'photo', 'disk' => 'public', 'file_path' => $path,
                        'album_name' => $data['album_name'], 'edition_id' => $data['edition_id'] ?? null, 'sort_order' => $index, 'is_published' => false]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk('public')->delete($path);
            } throw $e;
        }

        return redirect()->route('admin.media.index')->with('status', count($stored).' photos ajoutées en brouillon. Vérifie-les avant publication.');
    }
}
