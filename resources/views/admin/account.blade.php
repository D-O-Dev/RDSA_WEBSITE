@extends('admin.layout')
@section('admin-title', 'Mon compte')
@section('admin-content')
<div class="account-page">
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
<div class="account-intro"><span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span><div><h2>{{ auth()->user()->name }}</h2><p>{{ auth()->user()->is_admin ? 'Administrateur' : 'Contrôleur des billets' }} · Compte actif</p></div></div>
<div class="account-grid">
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
