@extends('admin.layout')
@section('admin-title', 'Inscription assistée')
@section('admin-content')
<div class="bar"><a class="btn" href="{{ route('admin.participations.index') }}">Retour aux réservations</a></div>
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<section class="card"><h3>Inscrire une personne</h3><p class="hint">Une personne, un billet. La demande restera en attente jusqu’à sa validation par l’équipe.</p>
@if($editions->isEmpty())<p>Aucune édition n’est ouverte aux réservations.</p>@else
<form method="POST" action="{{ route('admin.participations.store') }}">@csrf
<div class="f"><label for="assisted-edition">Édition</label><select class="in" id="assisted-edition" name="edition_id" required>@foreach($editions as $edition)<option value="{{ $edition->id }}">{{ $edition->name }}</option>@endforeach</select></div>
<div class="f"><label for="assisted-name">Nom complet</label><input class="in" id="assisted-name" name="full_name" value="{{ old('full_name') }}" minlength="2" maxlength="150" required></div>
<div class="f"><label for="assisted-phone">Téléphone</label><input class="in" id="assisted-phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" required></div>
<div class="f"><label for="assisted-reason">Motif de l’inscription assistée</label><input class="in" id="assisted-reason" name="reason" maxlength="1000" required></div>
<div class="act"><button class="btn" type="submit">Créer la demande</button></div></form>@endif</section>
@endsection
