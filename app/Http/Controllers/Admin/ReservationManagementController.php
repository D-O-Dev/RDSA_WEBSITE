<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Participation;
use App\Models\WhatsappDelivery;
use App\Services\ParticipationService;
use App\Services\ReservationDeletionService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationManagementController extends Controller
{
    public function create()
    {
        return view('admin.reservation-create', ['editions' => Edition::where('is_active', true)->where('is_published', true)->where('is_archived', false)->get()]);
    }

    public function store(Request $request, ParticipationService $service)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $record = $service->create($request->only(['edition_id', 'full_name', 'phone']) + [
            'submission_token' => (string) Str::uuid(), 'has_attended_before' => false, 'whatsapp_opt_in' => false,
        ]);
        $request->attributes->set('admin_action_target', 'Réservation #'.$record->id);
        $request->attributes->set('admin_action_reason', trim($request->input('reason')));

        return redirect()->route('admin.participations.index', ['q' => $record->reference])->with('status', 'Demande créée. Vérifie-la puis valide l’émission du billet.');
    }

    public function edit(Participation $participation)
    {
        return response()->view('admin.reservation-manage', ['reservation' => $participation->load(['participant', 'edition', 'tickets.attendance'])])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, Participation $participation)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\+|00)?[0-9\s().-]{8,25}$/'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $phone = ParticipationService::normalizePhone($data['phone']);
        $request->attributes->set('admin_action_reason', trim($data['reason']));
        $name = trim($data['full_name']);
        if (mb_strlen($name) < 2 || ! preg_match('/^[1-9]\d{7,14}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Vérifie le nom et le numéro de téléphone.']);
        }
        DB::transaction(function () use ($participation, $phone, $name) {
            Edition::whereKey($participation->edition_id)->lockForUpdate()->firstOrFail();
            $record = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            $tickets = $record->tickets()->orderBy('id')->lockForUpdate()->get();
            $this->deliveriesSettled($record);
            if (Participation::where('edition_id', $record->edition_id)->whereKeyNot($record->id)
                ->where(fn ($q) => $q->where('reservation_phone_hash', hash('sha256', $phone))->orWhereHas('participant', fn ($p) => $p->whereIn('phone', [$phone, '+'.$phone])))->exists()) {
                throw ValidationException::withMessages(['phone' => 'Ce téléphone possède déjà une réservation pour cette édition.']);
            }
            // Les inscriptions d'autres éditions ne doivent pas être modifiées avec ce dossier.
            $oldPerson = Participant::whereKey($record->participant_id)->lockForUpdate()->firstOrFail();
            $changedPhone = ParticipationService::normalizePhone($oldPerson->phone) !== $phone;
            $person = Participant::firstOrCreate(['full_name' => $name, 'phone' => $phone]);
            $record->update(['participant_id' => $person->id, 'reservation_phone_hash' => hash('sha256', $phone),
                'whatsapp_opt_in' => $changedPhone ? false : $record->whatsapp_opt_in]);
            if ($changedPhone) {
                WhatsappDelivery::whereIn('ticket_id', $tickets->modelKeys())->whereIn('status', ['pending', 'failed'])->update(['status' => 'cancelled']);
                foreach ($tickets as $ticket) {
                    if ($record->status === 'confirmed' && $ticket->status === 'valid') {
                        app(TicketService::class)->renew($ticket);
                    } else {
                        app(TicketService::class)->revokePrivateAccess($ticket);
                    }
                }
            }
            if ($oldPerson->id !== $person->id && ! $oldPerson->participations()->exists()) {
                $oldPerson->delete();
            }
        });

        return redirect()->route('admin.participations.manage', $participation)->with('status', 'Informations corrigées. Si le téléphone a changé, les anciens liens privés et les QR non utilisés sont révoqués ; l’envoi automatique WhatsApp est désactivé. Transmets le lien actuel au participant.');
    }

    public function undoEntry(Request $request, Participation $participation)
    {
        $this->authorizeOperation($request, $participation);
        DB::transaction(function () use ($participation) {
            $record = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            $tickets = $record->tickets()->orderBy('id')->lockForUpdate()->get();
            if ($record->status !== 'confirmed' || ! Attendance::whereIn('ticket_id', $tickets->modelKeys())->exists()) {
                throw ValidationException::withMessages(['operation' => 'Aucune entrée confirmée à annuler.']);
            }
            Attendance::whereIn('ticket_id', $tickets->modelKeys())->delete();
            $record->tickets()->where('status', 'used')->update(['status' => 'valid']);
        });

        return redirect()->route('admin.participations.manage', $participation)->with('status', 'Entrée annulée. Le billet peut de nouveau être contrôlé.');
    }

    public function purgeTest(Request $request, Participation $participation, ReservationDeletionService $service)
    {
        $this->authorizeOperation($request, $participation);
        $service->delete($participation, true);

        return redirect()->route('admin.participations.index')->with('status', 'Test supprimé : réservation, billet et présence effacés.');
    }

    public function reopen(Request $request, Participation $participation)
    {
        $this->authorizeOperation($request, $participation);
        DB::transaction(function () use ($participation) {
            $record = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            $tickets = $record->tickets()->orderBy('id')->lockForUpdate()->get();
            if (! in_array($record->status, ['rejected', 'cancelled']) || $tickets->contains('status', 'used') || Attendance::whereIn('ticket_id', $tickets->modelKeys())->exists()) {
                throw ValidationException::withMessages(['operation' => 'Seule une demande refusée ou annulée sans présence peut être réexaminée.']);
            }
            $this->deliveriesSettled($record);
            $record->tickets()->update(['status' => 'cancelled']);
            $record->update(['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'review_reason' => null]);
        });

        return redirect()->route('admin.participations.index', ['q' => $participation->reference])->with('status', 'Demande remise en attente. Les anciens billets restent invalides ; une nouvelle validation est nécessaire.');
    }

    public function replaceTicket(Request $request, Participation $participation, TicketService $service)
    {
        $this->authorizeOperation($request, $participation);
        $ticket = DB::transaction(function () use ($participation, $service) {
            $record = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            $ticket = $record->tickets()->lockForUpdate()->firstOrFail();
            if ($record->status !== 'confirmed' || $ticket->status !== 'valid' || $ticket->attendance()->exists()) {
                throw ValidationException::withMessages(['operation' => 'Seul un billet confirmé et non utilisé peut être remplacé.']);
            }
            $service->renew($ticket);

            return $ticket;
        });

        return redirect()->route('admin.tickets.show', $ticket)->with('status', 'Billet remplacé. L’ancien lien, le QR et la référence sont invalidés. Transmets le nouveau billet au participant.');
    }

    private function authorizeOperation(Request $request, Participation $participation): void
    {
        abort_unless($request->user()->can('manage-sensitive-data'), 403);
        $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'confirmation_reference' => ['required', Rule::in([$participation->reference])],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $request->attributes->set('admin_action_reason', trim($request->input('reason')));
    }

    private function deliveriesSettled(Participation $record): void
    {
        if (WhatsappDelivery::whereIn('ticket_id', $record->tickets()->pluck('id'))->whereIn('status', ['processing', 'uncertain'])->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['operation' => 'Vérifie d’abord l’envoi WhatsApp en cours ou incertain.']);
        }
    }
}
