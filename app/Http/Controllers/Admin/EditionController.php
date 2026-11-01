<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\MediaItem;
use App\Models\Participation;
use App\Models\SiteUpdate;
use App\Services\ParticipationService;
use App\Support\AdminImageUploads;
use App\Support\AppearanceRules;
use App\Support\EditionLocation;
use App\Support\ImageSource;
use App\Support\SharedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditionController extends Controller
{
    public function index(Request $request)
    {
        $editing = $request->filled('edit') ? Edition::findOrFail($request->integer('edit'))
            : ($request->boolean('create') ? new Edition(['is_published' => false, 'is_active' => false, 'is_archived' => false]) : null);
        $images = collect(File::allFiles(public_path('images')))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))
            ->map(fn ($file) => 'images/'.$file->getRelativePathname())->sort()->values();

        return view('admin.index', [
            'editions' => Edition::withCount('participations')->orderByDesc('starts_at')->get(),
            'editing' => $editing, 'images' => $images,
        ]);
    }

    public function store(Request $request)
    {
        return $this->withUpload($request, new Edition);
    }

    public function update(Request $request, Edition $edition)
    {
        return $this->withUpload($request, $edition);
    }

    private function withUpload(Request $request, Edition $edition)
    {
        $stored = AdminImageUploads::apply($request, ['hero_background_path']);
        try {
            return $this->save($request, $edition);
        } catch (\Throwable $e) {
            AdminImageUploads::discard($stored);
            throw $e;
        }
    }

    public function duplicate(Edition $edition)
    {
        $copy = DB::transaction(function () use ($edition) {
            Edition::orderBy('id')->lockForUpdate()->get();
            $number = (int) Edition::max('number') + 1;
            if ($number > 65535) {
                throw ValidationException::withMessages(['edition' => 'Numéro maximal atteint.']);
            }
            $copy = $edition->replicate();
            $copy->fill(['name' => 'RDSA '.$number, 'number' => $number, 'slug' => 'rdsa-'.$number.'-'.Str::lower(Str::random(8)),
                'is_active' => false, 'is_published' => false, 'is_archived' => false, 'reservations_open_at' => null, 'reservations_close_at' => null]);
            $copy->save();

            return $copy;
        });
        request()->attributes->set('admin_action_target', 'Édition #'.$copy->id);

        return redirect()->route('admin.index', ['edit' => $copy->id])->with('status', 'Brouillon dupliqué avec son apparence. Renseigne sa nouvelle date et ses informations avant publication.');
    }

    public function destroy(Request $request, Edition $edition)
    {
        abort_unless($request->user()->can('manage-sensitive-data'), 403);
        $request->validate(['current_password' => ['required', 'current_password:web'], 'confirmation_name' => ['required', Rule::in([$edition->name])]]);
        DB::transaction(function () use ($edition) {
            $record = Edition::whereKey($edition->id)->lockForUpdate()->firstOrFail();
            if ($record->is_active || $record->is_published || $record->participations()->exists()
                || MediaItem::withTrashed()->where('edition_id', $record->id)->exists()
                || SiteUpdate::withTrashed()->where('edition_id', $record->id)->exists()
                || Participation::whereJsonContains('previous_edition_ids', $record->id)->exists()) {
                throw ValidationException::withMessages(['edition' => 'Seul un brouillon sans données associées peut être supprimé. Archive cette édition pour conserver son historique.']);
            }
            $record->delete();
        });

        return redirect()->route('admin.index')->with('status', 'Brouillon supprimé.');
    }

    private function save(Request $request, Edition $edition)
    {
        $data = $request->validate([
            ...AppearanceRules::rules(),
            'name' => ['required', 'string', 'max:100'],
            'number' => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('editions')->ignore($edition->id)],
            'theme' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'venue' => ['required', 'string', 'max:255'],
            'venue_details' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+|00)?[0-9\s().-]{8,25}$/'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+|00)?[0-9\s().-]{8,25}$/'],
            'map_url' => ['nullable', 'string', 'max:2000', function ($attribute, $value, $fail) {
                if (! EditionLocation::validUrl($value)) {
                    $fail('Utilise un lien Google Maps HTTPS valide.');
                }
            }],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'reservations_open_at' => ['sometimes', 'nullable', 'date_format:Y-m-d\TH:i'],
            'reservations_close_at' => ['sometimes', 'nullable', 'date_format:Y-m-d\TH:i', ...($request->filled('reservations_open_at') ? ['after:reservations_open_at'] : [])],
            'is_published' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'],
            'is_archived' => ['required', 'boolean'],
            'hero_background_path' => ['nullable', 'string', 'max:2000', ImageSource::rule()],
        ]);
        if (isset($data['hero_settings']['slides'])) {
            $data['hero_settings']['slides'] = array_values(array_filter($data['hero_settings']['slides']));
        }
        foreach (['contact_phone', 'contact_whatsapp'] as $field) {
            if (! empty($data[$field])) {
                $number = ParticipationService::normalizePhone($data[$field]);
                if (! preg_match('/^[1-9]\d{7,14}$/', $number)) {
                    throw ValidationException::withMessages([$field => 'Indique un numéro de contact valide.']);
                }
                $data[$field] = '+'.$number;
            }
        }
        if (! empty($data['map_url'])) {
            $data['map_url'] = SharedLink::resolve($data['map_url'], 'map');
            if (! EditionLocation::validUrl($data['map_url'])) {
                throw ValidationException::withMessages(['map_url' => 'Le lien de localisation doit pointer vers Google Maps.']);
            }
        }
        if (! empty($data['map_url']) && ! (EditionLocation::data(new Edition($data))['embed'] ?? null)) {
            throw ValidationException::withMessages(['map_url' => 'Ce lien ne fournit pas encore de repère intégrable. Utilise l’URL d’intégration Google Maps, le lien complet du lieu, ou ajoute ses latitude et longitude pour afficher la vraie carte.']);
        }
        if ($data['is_active'] && (! $data['is_published'] || $data['is_archived'])) {
            throw ValidationException::withMessages(['is_active' => 'Une édition ouverte doit être publiée et non archivée.']);
        }
        DB::transaction(function () use ($edition, $data) {
            // Sérialiser l'ouverture des éditions : une seule reçoit les inscriptions.
            Edition::orderBy('id')->lockForUpdate()->get();
            if ($data['is_active']) {
                Edition::where('is_active', true)
                    ->when($edition->exists, fn ($query) => $query->whereKeyNot($edition->id))
                    ->update(['is_active' => false]);
            }
            if (! $edition->exists) {
                $edition->slug = Str::slug($data['name']).'-'.Str::lower(Str::random(8));
            }
            $edition->fill($data)->save();
        });
        $request->attributes->set('admin_action_target', 'Édition #'.$edition->id);

        return redirect()->route('admin.index')->with('status', 'Édition enregistrée.');
    }
}
