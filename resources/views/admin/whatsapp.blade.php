@extends('admin.layout')
@section('admin-title', 'WhatsApp')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="lock" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="card"><h3><i class="bi bi-whatsapp"></i>Envoi automatique du billet et du PDF</h3>
<p class="hint">Option indépendante du partage manuel. Elle nécessite un compte WhatsApp Cloud API de Meta et un modèle approuvé avec un document PDF. Des frais fournisseur peuvent s’appliquer.</p>
<form method="POST" action="{{ route('admin.whatsapp.update') }}">@csrf @method('PUT')
<label class="sw"><span>Envoyer automatiquement après validation de la demande</span><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->enabled))></label>
<div class="row2">
<div class="f"><label for="phone-number-id">Identifiant du numéro Meta</label><input class="in" id="phone-number-id" name="phone_number_id" value="{{ old('phone_number_id', $settings->phone_number_id) }}" inputmode="numeric"></div>
<div class="f"><label for="api-version">Version de l’API</label><input class="in" id="api-version" name="api_version" value="{{ old('api_version', $settings->api_version) }}" required></div>
</div>
<div class="row2">
<div class="f"><label for="template-name">Nom du modèle approuvé</label><input class="in" id="template-name" name="template_name" value="{{ old('template_name', $settings->template_name) }}" placeholder="rdsa_billet"></div>
<div class="f"><label for="template-language">Langue du modèle</label><input class="in" id="template-language" name="template_language" value="{{ old('template_language', $settings->template_language) }}" required></div>
</div>
<div class="f"><label for="public-base-url">Adresse publique HTTPS du site</label><input class="in" id="public-base-url" type="url" name="public_base_url" value="{{ old('public_base_url', $settings->public_base_url) }}" placeholder="https://ton-domaine.ci"></div>
<div class="f"><label for="access-token">Jeton d’accès Meta</label><input class="in" id="access-token" type="password" name="access_token" autocomplete="new-password"><div class="hint">{{ $settings->access_token ? 'Déjà enregistré. Laisse vide pour le conserver.' : 'Pas encore renseigné.' }}</div></div>
<div class="f"><label for="app-secret">Secret de l’application Meta</label><input class="in" id="app-secret" type="password" name="app_secret" autocomplete="new-password"><div class="hint">{{ $settings->app_secret ? 'Déjà enregistré. Laisse vide pour le conserver.' : 'Nécessaire pour vérifier les notifications de livraison.' }}</div></div>
<div class="act"><button class="btn pri" type="submit">Enregistrer</button></div>
</form></section>
<section class="card" style="margin-top:18px"><h3>Modèle et suivi de livraison</h3>
<p class="hint">Dans Meta, utilise un modèle avec un en-tête « document » et cinq variables de texte dans cet ordre : nom, édition, référence du billet, lien privé, code d’accès. Le PDF est joint au message.</p>
<div class="f"><label for="callback-url">URL de notification à renseigner dans Meta</label><input class="in" id="callback-url" value="{{ $settings->public_base_url ? rtrim($settings->public_base_url, '/') . '/webhooks/whatsapp' : route('whatsapp.webhook.verify') }}" readonly onclick="this.select()"></div>
<div class="f"><label for="verify-token">Code de vérification à renseigner dans Meta</label><input class="in" id="verify-token" type="password" value="{{ $settings->verify_token }}" readonly onclick="this.select()"></div>
<p class="hint">L’envoi automatique concerne les participants ayant demandé la réception WhatsApp. Un échec d’envoi n’annule jamais une réservation confirmée. Le partage manuel reste disponible.</p>
</section>
@endsection
