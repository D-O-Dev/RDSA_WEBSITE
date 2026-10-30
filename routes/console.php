<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rdsa:deliver-tickets', function () {
    // Une tentative interrompue ne doit pas être renvoyée aveuglément.
    \App\Models\WhatsappDelivery::where('status', 'processing')->where('attempted_at', '<', now()->subMinutes(5))
        ->update(['status' => 'uncertain', 'last_error' => 'Tentative interrompue. Vérifier WhatsApp avant tout nouvel envoi.']);
    \App\Models\WhatsappDelivery::where('status', 'pending')->orderBy('id')->limit(50)->get()
        ->each(fn ($delivery) => app(\App\Services\WhatsappTicketService::class)->send($delivery->id));
    $this->info('Envois en attente examinés.');
})->purpose('Récupérer les envois WhatsApp en attente');
