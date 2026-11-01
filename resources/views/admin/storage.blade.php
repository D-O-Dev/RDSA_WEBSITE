@extends('admin.layout')
@section('admin-title', 'Fichiers inutilisés')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<section class="card"><h3>Nettoyer les images téléversées</h3><p class="hint">Seules les images sans référence dans les éditions, contenus ou médias sont proposées. Les fichiers récents et ceux des contenus en corbeille restent protégés.</p>
<form method="POST" action="{{ route('admin.storage.destroy') }}">@csrf @method('DELETE')
@forelse($files as $file)<label class="sw"><span>{{ basename($file['path']) }} · {{ number_format($file['size'] / 1024, 0, ',', ' ') }} Ko</span><input type="checkbox" name="paths[]" value="{{ $file['path'] }}"></label>@empty<div class="empty">Aucun fichier inutilisé à nettoyer.</div>@endforelse
@if($files)<div class="f"><label>Ton mot de passe<input class="in" name="current_password" type="password" autocomplete="current-password" required></label></div><button class="btn bad" type="submit">Supprimer les fichiers sélectionnés</button>@endif</form></section>
@endsection
