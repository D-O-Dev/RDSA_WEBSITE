@extends('admin.layout')
@section('admin-title', 'Activer la double authentification')
@section('admin-content')
<section class="card"><h3>Associer ton application d’authentification</h3><p class="hint">Dans ton application d’authentification, ajoute un compte puis scanne ce QR. Cette page expire après dix minutes.</p><img src="{{ $qr }}" alt="QR de configuration de la double authentification" width="260" height="260"><details><summary>Utiliser la clé manuellement</summary><code>{{ $secret }}</code></details>
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.two-factor.enable') }}">@csrf<div class="f"><label for="setup-code">Code à six chiffres de l’application</label><input class="in" id="setup-code" name="two_factor_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" required></div><div class="f"><label for="setup-password">Ton mot de passe</label><input class="in" id="setup-password" name="current_password" type="password" autocomplete="current-password" required></div><div class="act"><a class="btn" href="{{ route('admin.account.edit') }}">Annuler</a><button class="btn" type="submit">Activer et obtenir mes codes de secours</button></div></form></section>
@endsection
