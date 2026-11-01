<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Participation;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExportController extends Controller
{
    public function __invoke(Request $request, string $kind)
    {
        $statuses = match ($kind) {
            'participations' => ['pending', 'confirmed', 'rejected', 'cancelled'],
            'tickets' => ['valid', 'used', 'cancelled', 'pending'],
            'attendances' => [],
            default => abort(404),
        };
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'edition' => ['nullable', 'integer', 'exists:editions,id'],
            'status' => ['nullable', Rule::in($statuses)],
        ]);
        $query = match ($kind) {
            'participations' => Participation::with(['participant', 'edition']),
            'tickets' => Ticket::with(['participation.participant', 'participation.edition']),
            'attendances' => Attendance::with(['ticket.participation.participant', 'ticket.participation.edition', 'checker']),
        };
        if (! empty($filters['edition'])) {
            $scope = fn ($q) => $q->where('edition_id', $filters['edition']);
            match ($kind) {
                'participations' => $scope($query),
                'tickets' => $query->whereHas('participation', $scope),
                'attendances' => $query->whereHas('ticket.participation', $scope),
            };
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $person = fn ($q) => $q->where('full_name', 'like', $term)->orWhere('phone', 'like', $term);
            $reservation = fn ($q) => $q->where('reference', 'like', $term)->orWhereHas('participant', $person);
            $ticket = fn ($q) => $q->where('reference', 'like', $term)->orWhereHas('participation', $reservation);
            $query->where(match ($kind) {
                'participations' => $reservation,
                'tickets' => $ticket,
                'attendances' => fn ($q) => $q->whereHas('ticket', $ticket),
            });
        }

        return response()->streamDownload(function () use ($query, $kind) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, match ($kind) {
                'participations' => ['Réservation', 'Nom', 'Téléphone', 'Édition', 'Statut', 'Inscription'],
                'tickets' => ['Billet', 'Réservation', 'Nom', 'Téléphone', 'Édition', 'Statut', 'Émission'],
                'attendances' => ['Billet', 'Nom', 'Téléphone', 'Édition', 'Entrée', 'Contrôleur'],
            }, ';', '"', '');
            $labels = ['pending' => 'En attente', 'confirmed' => 'Confirmée', 'rejected' => 'Refusée', 'cancelled' => 'Annulée', 'valid' => 'Valide', 'used' => 'Utilisé'];
            foreach ($query->lazyById(250) as $record) {
                $p = match ($kind) {
                    'participations' => $record,
                    'tickets' => $record->participation,
                    'attendances' => $record->ticket->participation,
                };
                $row = match ($kind) {
                    'participations' => [$p->reference, $p->participant->full_name, $p->participant->phone, $p->edition->name, $labels[$p->status] ?? $p->status, $p->created_at?->format('d/m/Y H:i')],
                    'tickets' => [$record->reference, $p->reference, $p->participant->full_name, $p->participant->phone, $p->edition->name, $labels[$record->status] ?? $record->status, $record->issued_at?->format('d/m/Y H:i')],
                    'attendances' => [$record->ticket->reference, $p->participant->full_name, $p->participant->phone, $p->edition->name, $record->checked_in_at?->format('d/m/Y H:i:s'), $record->checker?->name],
                };
                // Les noms saisis ne doivent jamais devenir des formules dans un tableur.
                $row = array_map(fn ($value) => preg_match('/^[\s]*[=+\-@]/u', (string) $value) ? "'".$value : $value, $row);
                fputcsv($file, $row, ';', '"', '');
            }
            fclose($file);
        }, 'rdsa-'.$kind.'-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
