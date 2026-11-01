<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\SiteContent;
use App\Services\ParticipationService;
use App\Support\AdminImageUploads;
use App\Support\EditionLocation;
use App\Support\ImageSource;
use App\Support\SharedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class SiteContentController extends Controller
{
    public function edit()
    {
        $images = collect(File::allFiles(public_path('images')))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))
            ->map(fn ($file) => 'images/'.$file->getRelativePathname())->sort()->values();

        return view('admin.content', ['groups' => config('site_content'), 'content' => SiteContent::current(), 'images' => $images]);
    }

    public function update(Request $request)
    {
        $stored = AdminImageUploads::apply($request, collect(SiteContent::fields())->filter(fn ($field) => ($field['type'] ?? '') === 'image')->keys()->all());
        try {
            $rules = [];
            foreach (SiteContent::fields() as $key => $field) {
                $rules[$key] = [($field['optional'] ?? false) ? 'nullable' : 'required', 'string', 'max:'.(($field['type'] ?? '') === 'textarea' ? 2000 : 500)];
                if (($field['type'] ?? '') === 'boolean') {
                    $rules[$key] = ['required', 'boolean'];
                }
                if (($field['type'] ?? '') === 'image') {
                    $rules[$key] = ['required', 'string', 'max:2000', ImageSource::rule()];
                }
            }
            $rules['ticket_logo_path'][] = function ($attribute, $value, $fail) {
                if (str_starts_with($value, 'https://')) {
                    $fail('Pour intégrer le logo au PDF, téléverse son image ou choisis un fichier déjà présent.');

                    return;
                }
                $path = str_starts_with($value, 'storage/rdsa/visuals/') ? storage_path('app/public/'.substr($value, 8)) : public_path($value);
                if (! extension_loaded('gd') && is_file($path) && mime_content_type($path) !== 'image/jpeg') {
                    $fail('Sur cet hébergement, utilise un logo JPG pour garantir son affichage dans le PDF.');
                }
            };
            foreach (['general_phone', 'general_whatsapp'] as $field) {
                $rules[$field][] = 'regex:/^(?:\+|00)?[0-9\s().-]{8,25}$/';
            }
            $rules['general_map_url'][] = function ($attribute, $value, $fail) {
                if (! EditionLocation::validUrl($value)) {
                    $fail('Indique un lien Google Maps HTTPS valide.');
                }
            };
            $rules['section_order'][] = function ($attribute, $value, $fail) {
                $sections = explode(',', $value);
                sort($sections);
                if ($sections !== ['contact', 'editions', 'medias', 'participer', 'rdsa']) {
                    $fail('Chaque rubrique doit apparaître une fois dans l’ordre choisi.');
                }
            };
            $labels = collect(SiteContent::fields())->map(fn ($field) => $field['label'])->all();
            $data = $request->validate($rules, [], $labels);
            foreach (SiteContent::fields() as $key => $field) {
                if (($field['optional'] ?? false) && ($data[$key] ?? null) === null) {
                    $data[$key] = '';
                }
            }
            foreach (['general_phone', 'general_whatsapp'] as $field) {
                if (! empty($data[$field])) {
                    $phone = ParticipationService::normalizePhone($data[$field]);
                    if (! preg_match('/^[1-9]\d{7,14}$/', $phone)) {
                        throw ValidationException::withMessages([$field => 'Indique un numéro valide.']);
                    }
                    $data[$field] = '+'.$phone;
                }
            }
            if (! empty($data['general_map_url'])) {
                $data['general_map_url'] = SharedLink::resolve($data['general_map_url'], 'map');
                $location = EditionLocation::data(new Edition(['map_url' => $data['general_map_url'], 'venue' => $data['general_venue'] ?? '']));
                if (! ($location['embed'] ?? null)) {
                    throw ValidationException::withMessages(['general_map_url' => 'Copie le lien Google Maps complet du lieu ou son lien d’intégration.']);
                }
            }
            if ($request->has('section_positions')) {
                $positions = $request->validate(['section_positions' => ['array:rdsa,editions,medias,participer,contact', 'size:5'], 'section_positions.*' => ['required', 'integer', 'between:1,5', 'distinct']])['section_positions'];
                asort($positions);
                $data['section_order'] = implode(',', array_keys($positions));
            }
            SiteContent::updateOrCreate(['key' => 'public'], ['values' => $data]);
        } catch (\Throwable $e) {
            AdminImageUploads::discard($stored);
            throw $e;
        }

        return redirect()->route('admin.content.edit')->with('status', 'Contenu du site enregistré.');
    }
}
