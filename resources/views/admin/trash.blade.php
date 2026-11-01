@extends('admin.layout')
@section('admin-title', 'Corbeille')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<div class="bar">@foreach(['media' => 'Médias', 'updates' => 'Annonces et FAQ', 'calendar' => 'Rendez-vous'] as $value => $label)<a class="btn {{ $kind === $value ? 'pri' : '' }}" href="{{ route('admin.trash.index', ['kind' => $value]) }}">{{ $label }}</a>@endforeach</div>
<section class="card"><h3>Éléments retirés</h3><p class="hint">Restaure un élément retiré par erreur ou efface-le définitivement. Restaurer un contenu ne le republie pas.</p>
@forelse($items as $item)<div class="card" style="margin-top:14px"><h3>{{ $item->title }}</h3><p class="hint">Retiré le {{ $item->deleted_at->format('d/m/Y H:i') }} · #{{ $item->id }}</p>
<form method="POST" action="{{ route('admin.trash.restore', ['kind' => $kind, 'id' => $item->id]) }}">@csrf<button class="btn" type="submit">Restaurer</button></form>
@can('manage-sensitive-data')<details class="update-delete"><summary>Effacer définitivement</summary><form method="POST" action="{{ route('admin.trash.destroy', ['kind' => $kind, 'id' => $item->id]) }}">@csrf @method('DELETE')<div class="f"><label>Recopie le numéro {{ $item->id }}<input class="in" name="confirmation_id" required></label></div><div class="f"><label>Ton mot de passe<input class="in" name="current_password" type="password" autocomplete="current-password" required></label></div><button class="btn bad" type="submit">Effacer définitivement</button></form></details>@endcan</div>
@empty<div class="empty">La corbeille est vide pour cette rubrique.</div>@endforelse</section>
@include('admin.partials.pagination', ['results' => $items])
@endsection
