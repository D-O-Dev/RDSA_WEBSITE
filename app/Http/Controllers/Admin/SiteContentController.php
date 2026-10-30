<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SiteContentController extends Controller
{
    public function edit()
    {
        $images = collect(File::allFiles(public_path('images')))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))
            ->map(fn ($file) => 'images/' . $file->getRelativePathname())->sort()->values();
        return view('admin.content', ['groups' => config('site_content'), 'content' => SiteContent::current(), 'images' => $images]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (SiteContent::fields() as $key => $field) {
            $rules[$key] = ['required', 'string', 'max:' . (($field['type'] ?? '') === 'textarea' ? 2000 : 500)];
            if (($field['type'] ?? '') === 'image') {
                $rules[$key][] = function ($attribute, $value, $fail) {
                    $root = realpath(public_path('images'));
                    $path = is_string($value) && !str_contains($value, '..') ? realpath(public_path($value)) : false;
                    if (!$root || !$path || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)
                        || !in_array(mime_content_type($path), ['image/jpeg', 'image/png', 'image/webp'])) {
                        $fail('Choisis une image JPG, PNG ou WebP du dossier public/images.');
                    }
                };
            }
        }
        $labels = collect(SiteContent::fields())->map(fn ($field) => $field['label'])->all();
        $data = $request->validate($rules, [], $labels);
        SiteContent::updateOrCreate(['key' => 'public'], ['values' => $data]);
        return redirect()->route('admin.content.edit')->with('status', 'Contenu du site enregistré.');
    }
}
