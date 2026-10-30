<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\WhatsappDelivery;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsappTicketService
{
    public function enqueue(Ticket $ticket): void
    {
        if (!WhatsappSetting::current()->enabled || !$ticket->participation->whatsapp_opt_in) return;
        $delivery = WhatsappDelivery::firstOrCreate(['ticket_id' => $ticket->id], ['status' => 'pending']);
        if ($delivery->status !== 'pending') return;
        DB::afterCommit(function () use ($delivery) {
            try { $this->send($delivery->id); } catch (Throwable) {
                // L'outbox reste consultable et récupérable sans annuler la réservation confirmée.
            }
        });
    }

    public function send(int $deliveryId): void
    {
        $settings = WhatsappSetting::current();
        if (!$settings->enabled) return;
        $claimed = DB::transaction(function () use ($deliveryId) {
            $delivery = WhatsappDelivery::whereKey($deliveryId)->lockForUpdate()->firstOrFail();
            if ($delivery->status !== 'pending') return false;
            $delivery->update(['status' => 'processing', 'attempts' => $delivery->attempts + 1, 'attempted_at' => now(), 'last_error' => null]);
            return true;
        });
        if (!$claimed) return;
        $sending = false;
        try {
            $delivery = WhatsappDelivery::findOrFail($deliveryId);
            $ticket = $delivery->ticket()->with(['participation.participant', 'participation.edition'])->firstOrFail();
            if (!$ticket->participation->whatsapp_opt_in || !$this->usable($ticket)) {
                $this->finish($deliveryId, 'cancelled', 'Envoi annulé : billet indisponible ou réception WhatsApp non demandée.');
                return;
            }
            $service = app(TicketService::class);
            $phone = $service->recipient($ticket);
            if (!$phone || !$settings->access_token || !$settings->phone_number_id || !$settings->template_name
                || !$settings->public_base_url || !str_starts_with($settings->public_base_url, 'https://')) {
                $this->finish($deliveryId, 'failed', 'Vérifie le numéro du participant et la configuration WhatsApp.');
                return;
            }
            $endpoint = 'https://graph.facebook.com/' . $settings->api_version . '/' . $settings->phone_number_id;
            $pdf = app(TicketDocumentService::class)->pdf($ticket);
            $upload = Http::withToken($settings->access_token)->acceptJson()->connectTimeout(5)->timeout(25)
                ->withOptions(['allow_redirects' => false])
                ->attach('file', $pdf, $ticket->reference . '.pdf', ['Content-Type' => 'application/pdf'])
                ->post($endpoint . '/media', ['messaging_product' => 'whatsapp', 'type' => 'application/pdf']);
            $mediaId = $upload->json('id');
            if (!$upload->successful() || !is_string($mediaId) || $mediaId === '') {
                $this->finish($deliveryId, 'failed', 'Téléversement PDF refusé par WhatsApp (HTTP ' . $upload->status() . ').');
                return;
            }
            $ticket->refresh();
            if (!$this->usable($ticket)) {
                $this->finish($deliveryId, 'cancelled', 'Le billet n’est plus disponible.');
                return;
            }
            $url = rtrim($settings->public_base_url, '/') . route('tickets.show', ['reference' => $ticket->reference, 'token' => $ticket->access_token], false);
            $values = [$ticket->participation->participant->full_name, $ticket->participation->edition->name,
                $ticket->reference, $url, $ticket->access_token];
            $sending = true;
            $response = Http::withToken($settings->access_token)->acceptJson()->asJson()->connectTimeout(5)->timeout(25)
                ->withOptions(['allow_redirects' => false])->post($endpoint . '/messages', [
                    'messaging_product' => 'whatsapp', 'to' => $phone, 'type' => 'template',
                    'template' => ['name' => $settings->template_name, 'language' => ['code' => $settings->template_language],
                        'components' => [
                            ['type' => 'header', 'parameters' => [['type' => 'document', 'document' => ['id' => $mediaId, 'filename' => $ticket->reference . '.pdf']]]],
                            ['type' => 'body', 'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => preg_replace('/\s+/u', ' ', $value)], $values)],
                        ]],
                ]);
            $messageId = $response->json('messages.0.id');
            if ($response->successful() && is_string($messageId) && $messageId !== '') {
                WhatsappDelivery::whereKey($deliveryId)->where('status', 'processing')->update([
                    'status' => 'accepted', 'provider_message_id' => $messageId, 'accepted_at' => now(), 'last_error' => null,
                ]);
            } elseif ($response->status() >= 400 && $response->status() < 500) {
                $this->finish($deliveryId, 'failed', 'Envoi refusé par WhatsApp (HTTP ' . $response->status() . '). Vérifie le modèle approuvé et les accès.');
            } else {
                $this->finish($deliveryId, 'uncertain', 'Résultat incertain. Vérifier WhatsApp avant tout nouvel envoi pour éviter un doublon.');
            }
        } catch (Throwable) {
            $this->finish($deliveryId, $sending ? 'uncertain' : 'failed', $sending
                ? 'Connexion interrompue pendant l’envoi. Vérifier WhatsApp avant de renvoyer.'
                : 'Impossible de préparer ou transmettre le PDF. Une relance est possible.');
        }
    }

    private function usable(Ticket $ticket): bool
    {
        return $ticket->status === 'valid' && $ticket->participation->status === 'confirmed';
    }

    private function finish(int $id, string $status, string $message): void
    {
        WhatsappDelivery::whereKey($id)->where('status', 'processing')->update(['status' => $status, 'last_error' => $message]);
    }
}
