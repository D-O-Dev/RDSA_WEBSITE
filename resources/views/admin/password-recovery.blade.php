@include('admin.head')
<body class="lg"><section class="lg-main"><div class="lg-c"><h2>{{ $reset ? 'Nouveau mot de passe' : 'Retrouver mon accès' }}</h2>
@if(session('status'))<p class="lock" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<p class="lock" role="alert">{{ $errors->first() }}</p>@endif
<form method="POST" action="{{ $reset ? route('password.update') : route('password.email') }}">@csrf
@if($reset)<input type="hidden" name="token" value="{{ $token }}">@endif
<div class="f"><label for="recovery-email">Adresse e-mail</label><input class="in" id="recovery-email" name="email" type="email" value="{{ old('email', $email ?? '') }}" autocomplete="username" required></div>
@if($reset)@foreach(['password' => 'Nouveau mot de passe', 'password_confirmation' => 'Confirmer le mot de passe'] as $field => $label)<div class="f"><label for="{{ $field }}">{{ $label }}</label><input class="in" id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="new-password" required></div>@endforeach<p class="hint">Au moins 12 caractères avec une majuscule, une minuscule, un chiffre et un symbole.</p>@endif
<div class="act"><a class="btn" href="{{ route('login') }}">Connexion</a><button class="btn" type="submit">{{ $reset ? 'Enregistrer le mot de passe' : 'Recevoir un lien privé' }}</button></div></form></div></section></body></html>
