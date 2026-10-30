<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\WhatsappDelivery;
use App\Models\WhatsappSetting;
use App\Services\WhatsappTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WhatsappController extends Controller
{
    public function edit()
    {
        return response()->view('admin.whatsapp', ['settings' => WhatsappSetting::current()])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'], 'phone_number_id' => ['nullable', 'regex:/^\d{5,30}$/'],
            'api_version' => ['required', 'regex:/^v\d{2}\.\d{1,2}$/'],
            'template_name' => ['nullable', 'regex:/^[a-z0-9_]{1,100}$/'],
            'template_language' => ['required', 'regex:/^[a-z]{2,3}(_[A-Z]{2})?$/'],
            'public_base_url' => ['nullable', 'url', 'max:500', 'regex:#^https://#'],
            'access_token' => ['nullable', 'string', 'max:4000'],
            'app_secret' => ['nullable', 'string', 'max:500'],
        ]);
        $settings = WhatsappSetting::current();
        foreach (['access_token', 'app_secret'] as $secret) {
            if (empty($data[$secret])) unset($data[$secret]);
        }
        $settings->fill($data);
        if ($settings->enabled && (!$settings->access_token || !$settings->app_secret || !$settings->phone_number_id
            || !$settings->template_name || !$settings->public_base_url)) {
            throw ValidationException::withMessages(['enabled' => 'Renseigne les accès Meta, le modèle approuvé et l’adresse HTTPS du site avant d’activer l’envoi.']);
        }
        $host = parse_url($settings->public_base_url ?? '', PHP_URL_HOST);
        if ($settings->enabled && (!$host || $host === 'localhost' || str_ends_with($host, '.localhost')
            || (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)))) {
            throw ValidationException::withMessages(['public_base_url' => 'Une adresse publique HTTPS est nécessaire pour les liens envoyés aux participants.']);
        }
        $settings->save();
        return redirect()->route('admin.whatsapp.edit')->with('status', 'Configuration WhatsApp enregistrée.');
    }

    public function retry(Ticket $ticket, WhatsappTicketService $service)
    {
        if (!WhatsappSetting::current()->enabled || !$ticket->participation->whatsapp_opt_in) {
            throw ValidationException::withMessages(['whatsapp' => 'Active WhatsApp et vérifie que le participant a demandé cet envoi.']);
        }
        $delivery = DB::transaction(function () use ($ticket) {
            $delivery = WhatsappDelivery::firstOrCreate(['ticket_id' => $ticket->id], ['status' => 'pending']);
            $delivery = WhatsappDelivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if (!in_array($delivery->status, ['pending', 'failed'])) {
                throw ValidationException::withMessages(['whatsapp' => 'Cet envoi est déjà traité, en cours ou incertain. Aucun doublon ne sera envoyé.']);
            }
            $delivery->update(['status' => 'pending']);
            return $delivery;
        });
        $service->send($delivery->id);
        return redirect()->route('admin.tickets.show', $ticket)->with('status', 'Tentative terminée. Consulte le statut WhatsApp ci-dessous.');
    }
}
