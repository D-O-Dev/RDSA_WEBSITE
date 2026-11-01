<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Edition;
use App\Models\MediaItem;
use App\Models\Participation;
use App\Models\WhatsappDelivery;
use App\Services\ReservationDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BulkController extends Controller
{
    public function __invoke(Request $request, string $kind, ReservationDeletionService $deletion)
    {
        abort_unless(in_array($kind, ['participations', 'media']), 404);
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['required', 'integer', 'distinct'],
            'operation' => ['required', Rule::in($kind === 'participations' ? ['cancel', 'purge-test'] : ['publish', 'hide', 'trash'])],
            'current_password' => ['required', 'current_password:web'], 'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'confirmation' => ['required', Rule::in(['CONFIRMER'])]]);
        if ($data['operation'] === 'purge-test') {
            abort_unless($request->user()->can('manage-sensitive-data'), 403);
        }
        DB::transaction(function () use ($request, $kind, $data, $deletion) {
            if ($kind === 'participations') {
                $editionIds = Participation::whereIn('id', $data['ids'])->orderBy('edition_id')->pluck('edition_id')->unique();
                Edition::whereIn('id', $editionIds)->orderBy('id')->lockForUpdate()->get();
                $records = Participation::whereIn('id', $data['ids'])->orderBy('id')->lockForUpdate()->get();
            } else {
                $records = MediaItem::whereIn('id', $data['ids'])->orderBy('id')->lockForUpdate()->get();
            }
            if ($records->count() !== count($data['ids'])) {
                throw ValidationException::withMessages(['ids' => 'Un élément sélectionné n’est plus disponible. Actualise la liste.']);
            }
            foreach ($records as $record) {
                if ($kind === 'media') {
                    if ($data['operation'] === 'trash') {
                        $record->delete();
                    } else {
                        $record->update(['is_published' => $data['operation'] === 'publish']);
                    }
                } elseif ($data['operation'] === 'purge-test') {
                    $deletion->delete($record, true);
                } else {
                    $tickets = $record->tickets()->orderBy('id')->lockForUpdate()->get();
                    if (! in_array($record->status, ['pending', 'confirmed']) || $tickets->contains('status', 'used') || Attendance::whereIn('ticket_id', $tickets->modelKeys())->exists()) {
                        throw ValidationException::withMessages(['operation' => 'La sélection contient une réservation déjà annulée, refusée ou utilisée. Aucun élément n’a été modifié.']);
                    }
                    $record->update(['status' => 'cancelled', 'review_reason' => $data['reason'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
                    $record->tickets()->update(['status' => 'cancelled']);
                    WhatsappDelivery::whereIn('ticket_id', $tickets->modelKeys())->whereIn('status', ['pending', 'failed'])->update(['status' => 'cancelled']);
                }
            }
        });
        $request->attributes->set('admin_action_target', $kind.' / '.$data['operation'].' : '.count($data['ids']).' éléments');
        $request->attributes->set('admin_action_details', ['ids' => $data['ids']]);
        $request->attributes->set('admin_action_reason', $data['reason']);

        return redirect()->route($kind === 'media' ? 'admin.media.index' : 'admin.participations.index')->with('status', count($data['ids']).' éléments traités.');
    }
}
