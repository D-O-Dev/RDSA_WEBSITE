@extends('admin.layout')
@section('admin-title', 'Mon compte')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
<div class="card" style="max-width:620px;margin:auto">
<h3><i class="bi bi-shield-lock" aria-hidden="true"></i> Sécuriser mon compte</h3>
<p class="hint" style="margin:16px 0;line-height:1.8">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
<form method="POST" action="{{ route('admin.account.update') }}">
@csrf @method('PUT')
@foreach(['current_password' => 'Mot de passe actuel', 'password' => 'Nouveau mot de passe', 'password_confirmation' => 'Confirmer le nouveau mot de passe'] as $name => $label)
<div class="f"><label for="account-{{ $name }}">{{ $label }}</label><input class="in" style="width:100%" id="account-{{ $name }}" type="password" name="{{ $name }}" autocomplete="{{ $name === 'current_password' ? 'current-password' : 'new-password' }}" required @if($errors->has($name)) aria-invalid="true" @endif>@error($name)<div class="hint" role="alert">{{ $message }}</div>@enderror</div>
@endforeach
<p class="hint" style="line-height:1.8">Choisis au moins 12 caractères avec une majuscule, une minuscule, un chiffre et un symbole.</p>
<div class="act"><button class="btn" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Changer mon mot de passe</button></div>
</form></div>
@endsection
