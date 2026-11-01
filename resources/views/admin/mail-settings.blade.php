@extends('admin.layout')
@section('admin-title', 'Envoi des e-mails')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<section class="card"><h3>Récupération des mots de passe</h3><p class="hint">Renseigne un compte SMTP fourni par ton service e-mail. Ces accès sont chiffrés et ne sont jamais affichés après enregistrement.</p><form method="POST" action="{{ route('admin.mail.update') }}">@csrf @method('PUT')
<label class="sw"><span>Activer ce compte d’envoi</span><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled'] ?? false))></label>
@foreach(['host' => 'Serveur SMTP', 'username' => 'Identifiant SMTP', 'from_address' => 'Adresse de l’expéditeur', 'from_name' => 'Nom de l’expéditeur'] as $field => $label)<div class="f"><label for="mail-{{ $field }}">{{ $label }}</label><input class="in" id="mail-{{ $field }}" name="{{ $field }}" value="{{ old($field, $settings[$field] ?? '') }}" required></div>@endforeach
<div class="row2"><div class="f"><label>Port<select class="in" name="port">@foreach([587, 465] as $port)<option value="{{ $port }}" @selected(old('port', $settings['port'] ?? 587) == $port)>{{ $port }}</option>@endforeach</select></label></div><div class="f"><label>Protection<select class="in" name="encryption"><option value="tls" @selected(old('encryption', $settings['encryption'] ?? 'tls') === 'tls')>STARTTLS</option><option value="ssl" @selected(old('encryption', $settings['encryption'] ?? '') === 'ssl')>TLS implicite</option></select></label></div></div>
<div class="f"><label>Mot de passe SMTP<input class="in" name="smtp_password" type="password" autocomplete="new-password"></label><p class="hint">Laisse vide pour conserver le mot de passe enregistré.</p></div><div class="f"><label>Ton mot de passe administrateur<input class="in" name="current_password" type="password" autocomplete="current-password" required></label></div><button class="btn" type="submit">Enregistrer</button></form></section>
@endsection
