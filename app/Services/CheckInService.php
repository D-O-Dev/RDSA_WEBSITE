<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Edition;
use App\Models\Participation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public function inspect(string $code, int $editionId): array
    {
        return $this->evaluate($code, $editionId, false);
    }

    public function confirm(string $code, int $editionId, User $agent): array
    {
        return $this->evaluate($code, $editionId, true, $agent);
    }

    private function evaluate(string $code, int $editionId, bool $confirm, ?User $agent = null): array
    {
        $code = trim($code);
        $qrToken = null;
        if (str_starts_with($code, 'RDSA1:')) {
            if (!preg_match('/^RDSA1:(RDSA-TK-[A-Z0-9]{12}):([a-f0-9]{64})$/', $code, $matches)) return $this->invalid();
            [, $reference, $qrToken] = $matches;
        } else {
            $reference = strtoupper($code);
            if (!preg_match('/^RDSA-TK-[A-Z0-9]{12}$/', $reference)) return $this->invalid();
        }
        $candidate = Ticket::where('reference', $reference)->first();
        if (!$candidate) return $this->invalid();
        return DB::transaction(function () use ($candidate, $editionId, $qrToken, $confirm, $agent) {
            // Même ordre de verrouillage que la validation et l'émission du billet.
            $participation = Participation::whereKey($candidate->participation_id)->lockForUpdate()->firstOrFail();
            $ticket = Ticket::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if ($qrToken !== null && !hash_equals($ticket->qr_token_hash, hash('sha256', $qrToken))) return $this->invalid();
            if ($participation->edition_id !== $editionId) {
                return ['ok' => false, 'status' => 'wrong_edition', 'message' => 'Ce billet appartient à une autre édition.', 'http' => 422];
            }
            $edition = Edition::findOrFail($editionId);
            if ($edition->is_archived || !$edition->is_published || $participation->status !== 'confirmed'
                || !in_array($ticket->status, ['valid', 'used'])) {
                return ['ok' => false, 'status' => 'unavailable', 'message' => 'Billet annulé ou réservation indisponible.', 'http' => 422];
            }
            $attendance = $ticket->attendance()->with('checker')->first();
            $details = ['reference' => $ticket->reference, 'participant' => $participation->participant->full_name,
                'edition' => $edition->name];
            if ($ticket->status === 'used' || $attendance) {
                return [...$details, 'ok' => false, 'status' => 'already_used',
                    'message' => 'Billet déjà utilisé. Nouvelle entrée refusée.', 'http' => 409,
                    'checked_at' => $attendance?->checked_in_at?->format('d/m/Y H:i:s'),
                    'checked_by' => $attendance?->checker?->name];
            }
            if (!$confirm) return [...$details, 'ok' => true, 'can_confirm' => true, 'status' => 'valid',
                'message' => 'Billet valide. Confirme l’entrée pour enregistrer le passage.', 'http' => 200];
            $attendance = Attendance::create(['ticket_id' => $ticket->id, 'checked_by' => $agent->id, 'checked_in_at' => now()]);
            $ticket->update(['status' => Ticket::STATUS_USED]);
            return [...$details, 'ok' => true, 'status' => 'used', 'message' => 'Entrée validée.', 'http' => 200,
                'checked_at' => $attendance->checked_in_at->format('d/m/Y H:i:s'), 'checked_by' => $agent->name];
        }, 3);
    }

    private function invalid(): array
    {
        return ['ok' => false, 'status' => 'invalid', 'message' => 'Billet inconnu ou QR code invalide.', 'http' => 422];
    }
}
