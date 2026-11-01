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
                $rules[$key] = ['required', 'string', 'max:2000', \App\Support\ImageSource::rule()];
            }
        }
        $labels = collect(SiteContent::fields())->map(fn ($field) => $field['label'])->all();
        $data = $request->validate($rules, [], $labels);
        SiteContent::updateOrCreate(['key' => 'public'], ['values' => $data]);
        return redirect()->route('admin.content.edit')->with('status', 'Contenu du site enregistré.');
    }
}
