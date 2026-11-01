<?php

namespace App\Services;

use App\Models\Participation;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function issue(Participation $participation): Ticket
    {
        return DB::transaction(function () use ($participation) {
            $locked = Participation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== Participation::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['ticket' => 'La réservation doit être confirmée avant l’émission du billet.']);
            }
            $ticket = $locked->tickets()->first();
            if ($ticket) {
                if ($ticket->status === Ticket::STATUS_CANCELLED) {
                    if ($ticket->attendance()->exists()) {
                        throw ValidationException::withMessages(['ticket' => 'Ce billet possède une présence.']);
                    }
                    $this->renew($ticket);
                    app(WhatsappTicketService::class)->enqueue($ticket);
                }

                return $ticket;
            }

            $qrToken = bin2hex(random_bytes(32));
            $accessToken = strtoupper(bin2hex(random_bytes(16)));
            $ticket = new Ticket([
                'participation_id' => $locked->id,
                'ticket_number' => 1,
                'reference' => 'RDSA-TK-'.strtoupper(Str::random(12)),
                'qr_token_hash' => hash('sha256', $qrToken),
                'status' => Ticket::STATUS_VALID,
                'issued_at' => now(),
            ]);
            $ticket->qr_token = $qrToken;
            $ticket->access_token = $accessToken;
            $ticket->access_token_hash = hash('sha256', $accessToken);
            $ticket->save();
            app(WhatsappTicketService::class)->enqueue($ticket);

            return $ticket;
        }, 3);
    }

    public function publicUrl(Ticket $ticket): string
    {
        return route('tickets.show', ['reference' => $ticket->reference, 'token' => $ticket->access_token]);
    }

    public function renew(Ticket $ticket): void
    {
        if ($ticket->whatsappDelivery()->whereIn('status', ['processing', 'uncertain'])->exists()) {
            throw ValidationException::withMessages(['ticket' => 'Vérifie d’abord l’envoi WhatsApp en cours ou incertain.']);
        }
        $qr = bin2hex(random_bytes(32));
        $access = strtoupper(bin2hex(random_bytes(16)));
        $ticket->forceFill(['reference' => 'RDSA-TK-'.strtoupper(Str::random(12)), 'qr_token' => $qr,
            'qr_token_hash' => hash('sha256', $qr), 'access_token' => $access, 'access_token_hash' => hash('sha256', $access),
            'status' => Ticket::STATUS_VALID, 'issued_at' => now()])->save();
        $ticket->whatsappDelivery()->delete();
    }

    public function revokePrivateAccess(Ticket $ticket): void
    {
        $access = strtoupper(bin2hex(random_bytes(16)));
        $ticket->forceFill(['access_token' => $access, 'access_token_hash' => hash('sha256', $access)])->save();
    }

    public function qrPayload(Ticket $ticket): string
    {
        return 'RDSA1:'.$ticket->reference.':'.$ticket->qr_token;
    }

    public function recipient(Ticket $ticket): ?string
    {
        $phone = preg_replace('/[\s().-]+/', '', trim($ticket->participation->participant->phone));
        if (preg_match('/^\d{10}$/', $phone)) {
            $phone = '225'.$phone;
        } elseif (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        } elseif (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }
        if (! preg_match('/^[1-9]\d{7,14}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    public function whatsappUrl(Ticket $ticket): ?string
    {
        $phone = $this->recipient($ticket);
        if (! $phone) {
            return null;
        }
        $message = 'Bonjour '.$ticket->participation->participant->full_name
            .', ta réservation pour '.$ticket->participation->edition->name." est confirmée.\n"
            ."Voici ton billet individuel :\n".$this->publicUrl($ticket)
            ."\nPDF : ".$this->publicUrl($ticket).'?download=1'
            ."\nRéférence : ".$ticket->reference
            ."\nCode d’accès : ".$ticket->access_token
            ."\nPrésente le QR code ou la référence à l’entrée. Ce billet permet une seule entrée."
            ."\nGarde ce lien et ce code privés.";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
