@extends('admin.layout')
@section('admin-title', 'Billet individuel')
@section('admin-content')
<div class="bar"><a class="btn" href="{{ route('admin.tickets.index') }}"><i class="bi bi-arrow-left"></i>Billets</a></div>
<div class="bar"><a class="btn" href="{{ route('admin.participations.manage', $ticket->participation) }}">Gérer la réservation / corriger un test</a></div>
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
<section class="card ticket-details">
<h3><i class="bi bi-ticket-perforated"></i>{{ $ticket->reference }}</h3>
<div class="kv ticket-facts">
<span>Participant</span><b>{{ $ticket->participation->participant->full_name }}</b>
<span>Téléphone</span><b>{{ $ticket->participation->participant->phone }}</b>
<span>Édition</span><b>{{ $ticket->participation->edition->name }}</b>
<span>Participation</span><b>{{ $ticket->participation->reference }}</b>
<span>Statut</span><div>@include('admin.partials.status', ['status' => $ticket->status])</div>
<span>Émis le</span><b>{{ $ticket->issued_at?->format('d/m/Y H:i') }}</b>
</div>
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
@if($publicUrl)
<div class="f"><label for="ticket-link">Lien privé du billet</label><input class="in" id="ticket-link" value="{{ $publicUrl }}" readonly onclick="this.select()"></div>
<div class="f"><label for="ticket-code">Code d’accès privé</label><input class="in" id="ticket-code" value="{{ $ticket->access_token }}" readonly onclick="this.select()"></div>
<div class="act">
<a class="btn" href="{{ $publicUrl }}?download=1"><i class="bi bi-download"></i>Télécharger le PDF</a>
<a class="btn" href="{{ $publicUrl }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-eye"></i>Ouvrir le billet</a>
@if($available && $whatsappUrl)<a class="btn pri" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i>Envoyer sur WhatsApp</a>@endif
</div>
@if($available)
<p class="hint">Le bouton ouvre WhatsApp avec le destinataire et le message préparés. Confirme l’envoi dans WhatsApp.</p>
@if(!$whatsappUrl)
<p class="hint">Le numéro WhatsApp n’est pas exploitable. Vérifie-le avec le participant avant de partager son lien privé.</p>
@endif
@else
<p class="hint">Ce billet reste consultable. Son statut ne permet pas un nouvel envoi WhatsApp.</p>
@endif
@else<p class="hint">Ce billet n’est pas disponible pour un nouvel envoi.</p>@endif
</section>
<section class="card" style="margin-top:18px"><h3>Envoi automatique WhatsApp</h3>
@php
$delivery = $ticket->whatsappDelivery;
$deliveryLabels = ['pending' => 'En attente', 'processing' => 'En cours', 'accepted' => 'Accepté par Meta — livraison non confirmée', 'delivered' => 'Livré', 'read' => 'Lu', 'failed' => 'Échec', 'uncertain' => 'Résultat incertain', 'cancelled' => 'Annulé'];
@endphp
<div class="kv"><span>Option automatique</span><b>{{ $whatsappEnabled ? 'Activée' : 'Désactivée' }}</b>
<span>Réception demandée</span><b>{{ $ticket->participation->whatsapp_opt_in ? 'Oui' : 'Non' }}</b>
<span>Statut</span><b>{{ $delivery ? ($deliveryLabels[$delivery->status] ?? $delivery->status) : 'Aucun envoi automatique' }}</b>
@if($delivery)<span>Tentatives</span><b>{{ $delivery->attempts }}</b>@endif
@if($delivery?->delivered_at)<span>Livraison confirmée</span><b>{{ $delivery->delivered_at->format('d/m/Y H:i') }}</b>@endif
</div>
@if($delivery?->last_error)<p class="hint" role="status">{{ $delivery->last_error }}</p>@endif
@if($available && $whatsappEnabled && $ticket->participation->whatsapp_opt_in && (!$delivery || in_array($delivery->status, ['pending', 'failed'])))
<form method="POST" action="{{ route('admin.whatsapp.retry', $ticket) }}">@csrf<div class="act"><button class="btn pri" type="submit">{{ $delivery ? 'Relancer l’envoi automatique' : 'Envoyer via Meta' }}</button></div></form>
@endif
</section>
<section class="card" style="margin-top:18px"><h3>Gestion de la réservation</h3>@include('admin.partials.delete-reservation', ['reservation' => $ticket->participation])@if(!in_array($ticket->participation->status, ['cancelled', 'rejected']))<p class="hint">Pour supprimer ce billet, annule d’abord sa réservation depuis la liste des réservations. Une présence enregistrée reste protégée.</p><a class="btn" href="{{ route('admin.participations.index', ['q' => $ticket->participation->reference]) }}">Voir la réservation</a>@endif</section>
@endsection
