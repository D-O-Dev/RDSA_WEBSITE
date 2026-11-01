@extends('admin.layout')
@section('admin-title', 'Mon compte')
@section('admin-content')
<div class="account-page">
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
<div class="account-intro"><span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span><div><h2>{{ auth()->user()->name }}</h2><p>{{ auth()->user()->is_super_admin ? 'Super ADMIN' : (auth()->user()->is_admin ? 'Administrateur' : 'Contrôleur des billets') }} · Compte actif</p></div></div>
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<div class="account-grid">
@if(auth()->user()->is_admin)<section class="card"><h3>Alertes sur cet appareil</h3><p class="hint">Reçois une alerte pour les nouvelles réservations et les nouveaux messages, même lorsque la page est fermée si ton navigateur le permet.</p>@include('partials.push-toggle', ['pushScope' => 'admin'])</section>@endif
<section class="card"><h3>Double authentification</h3><p class="hint">{{ auth()->user()->two_factor_secret ? 'Activée : un code de ton application ou un code de secours est demandé à la connexion.' : 'Ajoute un code temporaire à ton mot de passe pour protéger ton compte.' }}</p><form method="POST" action="{{ auth()->user()->two_factor_secret ? route('admin.two-factor.disable') : route('admin.two-factor.setup') }}">@csrf @if(auth()->user()->two_factor_secret) @method('DELETE')<div class="f"><label>Code de l’application ou code de secours<input class="in" name="two_factor_code" autocomplete="one-time-code" maxlength="32" required></label></div>@endif<div class="f"><label>Ton mot de passe<input class="in" name="current_password" type="password" autocomplete="current-password" required></label></div><button class="btn" type="submit">{{ auth()->user()->two_factor_secret ? 'Désactiver la double authentification' : 'Configurer la double authentification' }}</button></form></section>
<section class="card" aria-labelledby="profile-title">
<h3 id="profile-title"><i class="bi bi-person" aria-hidden="true"></i> Mon profil</h3>
<p class="hint account-description">Ton nom apparaît dans l’administration. Ton adresse e-mail sert à te connecter.</p>
<form method="POST" action="{{ route('admin.account.profile') }}">
@csrf @method('PUT')
@foreach(['name' => 'Nom affiché', 'email' => 'Adresse e-mail de connexion'] as $name => $label)
<div class="f"><label for="profile-{{ $name }}">{{ $label }}</label><input class="in" id="profile-{{ $name }}" type="{{ $name === 'email' ? 'email' : 'text' }}" name="{{ $name }}" value="{{ $errors->profile->any() ? old($name, auth()->user()->$name) : auth()->user()->$name }}" autocomplete="{{ $name }}" maxlength="255" required @if($errors->profile->has($name)) aria-invalid="true" aria-describedby="profile-{{ $name }}-error" @endif>@error($name, 'profile')<p class="hint" id="profile-{{ $name }}-error" role="alert">{{ $message }}</p>@enderror</div>
@endforeach
@include('admin.partials.account-password', ['field' => 'current_password', 'label' => 'Mot de passe actuel', 'prefix' => 'profile', 'bag' => 'profile'])
<div class="act"><button class="btn" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Enregistrer mon profil</button></div>
</form>
</section>
<section class="card" aria-labelledby="password-title">
<h3 id="password-title"><i class="bi bi-shield-lock" aria-hidden="true"></i> Mon mot de passe</h3>
<p class="hint account-description">Après le changement, les autres appareils devront se reconnecter.</p>
<form method="POST" action="{{ route('admin.account.update') }}">
@csrf @method('PUT')
@foreach(['current_password' => 'Mot de passe actuel', 'password' => 'Nouveau mot de passe', 'password_confirmation' => 'Confirmer le nouveau mot de passe'] as $name => $label)
@include('admin.partials.account-password', ['field' => $name, 'label' => $label, 'prefix' => 'password', 'bag' => 'default'])
@endforeach
<p class="hint account-description" id="account-password-help">Au moins 12 caractères, avec une majuscule, une minuscule, un chiffre et un symbole.</p>
<div class="act"><button class="btn" type="submit"><i class="bi bi-key" aria-hidden="true"></i>Changer mon mot de passe</button></div>
</form>
</section>
<section class="card account-sessions" aria-labelledby="sessions-title">
<div><h3 id="sessions-title"><i class="bi bi-laptop" aria-hidden="true"></i> Mes connexions</h3><p class="hint account-description">Tu as utilisé un appareil partagé ? Ferme tes autres connexions sans quitter celle-ci. La déconnexion prendra effet à leur prochaine action.</p></div>
<form method="POST" action="{{ route('admin.account.sessions.revoke') }}">
@csrf
@include('admin.partials.account-password', ['field' => 'current_password', 'label' => 'Confirmer mon mot de passe', 'prefix' => 'sessions', 'bag' => 'sessions'])
<div class="act"><button class="btn" type="submit"><i class="bi bi-shield-check" aria-hidden="true"></i>Déconnecter les autres appareils</button></div>
</form>
</section>
</div></div>
@endsection
