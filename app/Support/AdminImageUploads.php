<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminImageUploads
{
    public static function apply(Request $request, array $allowed): array
    {
        $rules = [];
        foreach ($allowed as $field) {
            $rules['images.'.$field] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }
        $request->validate($rules);
        $stored = [];
        foreach ($allowed as $field) {
            if (! $request->hasFile('images.'.$field)) {
                continue;
            }
            $path = $request->file('images.'.$field)->store('rdsa/visuals', 'public');
            if (! $path) {
                throw new \RuntimeException('Impossible d’enregistrer cette image.');
            }
            $stored[] = $path;
            $request->merge([$field => 'storage/'.$path]);
        }

        return $stored;
    }

    public static function discard(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
