<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\SiteBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BackupController extends Controller
{
    public function index(SiteBackupService $service)
    {
        return view('admin.backups', ['backups' => $service->listing(), 'settings' => ApplicationSetting::find('maintenance')?->values ?? [], 'schedule' => ApplicationSetting::find('backups')?->values ?? []]);
    }

    public function store(Request $request, SiteBackupService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);
        $id = $service->create();
        $request->attributes->set('admin_action_target', 'Sauvegarde '.$id);

        return redirect()->route('admin.backups.index')->with('status', 'Sauvegarde privée créée. Télécharge-la et conserve-la hors de l’hébergement.');
    }

    public function download(string $id, SiteBackupService $service)
    {
        return response()->download($service->path($id), 'rdsa-sauvegarde-'.$id.'.zip', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function restore(Request $request, string $id, SiteBackupService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web'], 'confirmation' => ['required', Rule::in(['RESTAURER'])], 'reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $service->path($id);
        $service->verify($id);
        $before = $service->create();
        $service->restore($id);
        $request->attributes->set('admin_action_target', 'Sauvegarde '.$id);
        $request->attributes->set('admin_action_details', ['before' => $before]);
        $request->attributes->set('admin_action_reason', $request->input('reason'));

        return redirect()->route('admin.backups.index')->with('status', 'Données restaurées. Une sauvegarde de l’état précédent a été conservée. Vérifie les réservations et les envois WhatsApp.');
    }

    public function import(Request $request, SiteBackupService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web'], 'archive' => ['required', 'file', 'mimes:zip', 'max:153600']]);
        $id = (string) Str::uuid();
        $path = $service->directory().'/'.$id.'.zip';
        $request->file('archive')->move($service->directory(), $id.'.zip');
        chmod($path, 0600);
        try {
            $service->verify($id);
        } catch (\Throwable) {
            @unlink($path);
            throw ValidationException::withMessages(['archive' => 'Archive incompatible, endommagée ou créée avec une autre clé applicative.']);
        }

        return redirect()->route('admin.backups.index')->with('status', 'Archive vérifiée et ajoutée à la liste. Aucune donnée n’a encore été restaurée.');
    }

    public function destroy(Request $request, string $id, SiteBackupService $service)
    {
        $request->validate(['current_password' => ['required', 'current_password:web'], 'confirmation' => ['required', Rule::in(['SUPPRIMER'])]]);
        unlink($service->path($id));

        return redirect()->route('admin.backups.index')->with('status', 'Archive supprimée.');
    }

    public function maintenance(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password:web'], 'enabled' => ['required', 'boolean'], 'message' => ['required', 'string', 'max:1000']]);
        unset($data['current_password']);
        ApplicationSetting::updateOrCreate(['key' => 'maintenance'], ['values' => $data]);

        return redirect()->route('admin.backups.index')->with('status', $data['enabled'] ? 'Site public temporairement fermé. L’administration reste accessible.' : 'Site public rouvert.');
    }

    public function schedule(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password:web'], 'enabled' => ['required', 'boolean'], 'time' => ['required', 'date_format:H:i']]);
        unset($data['current_password']);
        $previous = ApplicationSetting::find('backups')?->values ?? [];
        ApplicationSetting::updateOrCreate(['key' => 'backups'], ['values' => array_replace($previous, $data)]);

        return redirect()->route('admin.backups.index')->with('status', 'Horaire enregistré. L’exécution automatique nécessite le planificateur de l’hébergement.');
    }
}
