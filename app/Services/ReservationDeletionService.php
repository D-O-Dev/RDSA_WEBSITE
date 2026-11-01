<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Participation;
use App\Models\WhatsappDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationDeletionService
{
    public function delete(Participation $participation): void
    {
        DB::transaction(function () use ($participation) {
            // Same order as registration; check-in also locks participation before tickets.
            Edition::whereKey($participation->edition_id)->lockForUpdate()->firstOrFail();
            $record = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            $tickets = $record->tickets()->orderBy('id')->lockForUpdate()->get();
            if (!in_array($record->status, ['cancelled', 'rejected']) || $tickets->contains('status', 'used')
                || Attendance::whereIn('ticket_id', $tickets->modelKeys())->exists()) {
                throw ValidationException::withMessages(['deletion' => 'Annule ou refuse d’abord cette réservation. Une présence enregistrée ne peut pas être effacée ici.']);
            }
            $deliveries = WhatsappDelivery::whereIn('ticket_id', $tickets->modelKeys())->lockForUpdate()->get();
            if ($deliveries->whereIn('status', ['processing', 'uncertain'])->isNotEmpty()) {
                throw ValidationException::withMessages(['deletion' => 'Un envoi WhatsApp est en cours ou incertain. Vérifie son résultat avant de supprimer la réservation.']);
            }
            if (DB::table('payments')->where('participation_id', $record->id)->exists()) {
                throw ValidationException::withMessages(['deletion' => 'Cette ancienne réservation possède un historique de paiement. Conserve-la annulée pour préserver cet historique.']);
            }
            WhatsappDelivery::whereIn('ticket_id', $tickets->modelKeys())->delete();
            $record->tickets()->delete();
            $person = Participant::whereKey($record->participant_id)->lockForUpdate()->firstOrFail();
            $record->delete();
            if (!$person->participations()->exists()) $person->delete();
        });
    }
}
