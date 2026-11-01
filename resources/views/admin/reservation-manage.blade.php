@extends('admin.layout')
@section('admin-title', 'Gérer la réservation')
@section('admin-content')
<div class="bar"><a class="btn" href="{{ route('admin.participations.index', ['q' => $reservation->reference]) }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Réservations</a></div>
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<section class="card"><h3>{{ $reservation->reference }}</h3><p class="hint">{{ $reservation->edition->name }} · {{ $reservation->participant->full_name }}</p>
<form method="POST" action="{{ route('admin.participations.correct', $reservation) }}">@csrf @method('PUT')
<div class="row2"><div class="f"><label for="correct-name">Nom complet</label><input class="in" id="correct-name" name="full_name" value="{{ old('full_name', $reservation->participant->full_name) }}" maxlength="150" required></div><div class="f"><label for="correct-phone">Téléphone</label><input class="in" id="correct-phone" name="phone" type="tel" value="{{ old('phone', $reservation->participant->phone) }}" maxlength="30" required></div></div>
<div class="f"><label for="correct-reason">Motif de la correction</label><input class="in" id="correct-reason" name="reason" maxlength="1000" required></div>
<p class="hint">La correction concerne uniquement cette réservation. Le billet téléchargé avant la correction n’est pas modifié sur le téléphone du participant.</p><div class="act"><button class="btn" type="submit">Enregistrer la correction</button></div></form></section>
@can('manage-sensitive-data')
<section class="card" style="margin-top:18px"><h3>Actions du Super ADMIN</h3>
@foreach(['undo-entry' => ['Annuler une entrée enregistrée par erreur', 'La présence sera retirée et le billet pourra être scanné à nouveau.'], 'reopen' => ['Réexaminer une demande refusée ou annulée', 'La demande repasse en attente. Les anciens billets restent invalides.'], 'replace-ticket' => ['Remplacer un billet partagé ou compromis', 'Le lien privé, la référence et le QR précédents seront révoqués. Le nouveau billet devra être transmis au participant.'], 'purge-test' => ['Supprimer entièrement cette réservation de test', 'La réservation, son billet et sa présence seront supprimés définitivement. Le billet téléchargé deviendra inutilisable.']] as $operation => $texts)
<details class="update-delete"><summary>{{ $texts[0] }}</summary><p class="hint">{{ $texts[1] }}</p><form method="POST" action="{{ route('admin.participations.' . $operation, $reservation) }}">@csrf
<div class="f"><label for="ref-{{ $operation }}">Recopie {{ $reservation->reference }}</label><input class="in" id="ref-{{ $operation }}" name="confirmation_reference" required autocomplete="off"></div>
<div class="f"><label for="reason-{{ $operation }}">Motif</label><input class="in" id="reason-{{ $operation }}" name="reason" minlength="3" maxlength="1000" required></div>
<div class="f"><label for="password-{{ $operation }}">Ton mot de passe</label><input class="in" id="password-{{ $operation }}" name="current_password" type="password" required autocomplete="current-password"></div>
<div class="act"><button class="btn bad" type="submit">{{ $texts[0] }}</button></div></form></details>
@endforeach
</section>@endcan
@endsection
