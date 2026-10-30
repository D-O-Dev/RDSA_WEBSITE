@extends('admin.layout')
@section('admin-title', 'Comptes de l’équipe')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
<div class="bar"><a class="btn" href="{{ route('admin.team.index', ['create' => 1]) }}"><i class="bi bi-person-plus" aria-hidden="true"></i>Ajouter un membre</a></div>
<div class="team-role-guide"><div class="card"><i class="bi bi-shield-check" aria-hidden="true"></i><h3>Administrateur</h3><p>Gère les éditions, les réservations, les contenus et les comptes.</p></div><div class="card"><i class="bi bi-qr-code-scan" aria-hidden="true"></i><h3>Contrôleur des billets</h3><p>Scanne les billets, confirme les entrées et consulte les présences.</p></div></div>
<form class="bar" method="GET"><div class="srch"><i class="bi bi-search" aria-hidden="true"></i><input name="q" value="{{ request('q') }}" placeholder="Rechercher un nom ou un e-mail" maxlength="150" aria-label="Rechercher un membre"></div><button class="btn" type="submit">Rechercher</button></form>
<div class="card"><div class="tw"><table><thead><tr><th>Membre</th><th>Accès</th><th>État</th><th></th></tr></thead><tbody>
@forelse($members as $member)
<tr><td><b>{{ $member->name }}</b><div class="hint">{{ $member->email }}</div></td><td>{{ $member->is_admin ? 'Administrateur' : 'Contrôleur des billets' }}</td><td><span class="bd {{ $member->is_active ? 'ok' : 'mu' }}">{{ $member->is_active ? 'Actif' : 'Désactivé' }}</span></td><td><a class="btn sm" href="{{ route('admin.team.index', ['edit' => $member->id]) }}">Modifier</a></td></tr>
@empty<tr><td colspan="4" class="empty">Aucun compte ne correspond à la recherche.</td></tr>@endforelse
</tbody></table></div></div>
@include('admin.partials.pagination', ['results' => $members])
@endsection
@section('admin-modal')
@if($editing)
<div class="ov on"><div class="md" role="dialog" aria-modal="true" aria-labelledby="team-editor-title"><form method="POST" action="{{ $editing->exists ? route('admin.team.update', $editing) : route('admin.team.store') }}">
@csrf @if($editing->exists) @method('PUT') @endif
<div class="media-editor-heading"><h3 id="team-editor-title">{{ $editing->exists ? 'Modifier le compte' : 'Ajouter un membre' }}</h3><a class="btn media-editor-close" href="{{ route('admin.team.index') }}" aria-label="Fermer le formulaire membre"><i class="bi bi-x-lg" aria-hidden="true"></i></a></div>
@if($errors->any())<div class="lock" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="f"><label for="member-name">Nom complet</label><input class="in" id="member-name" name="name" value="{{ old('name', $editing->name) }}" maxlength="255" required></div>
<div class="f"><label for="member-email">Adresse e-mail</label><input class="in" id="member-email" name="email" type="email" value="{{ old('email', $editing->email) }}" maxlength="255" autocomplete="off" required></div>
<div class="f"><label for="member-role">Accès autorisé</label><select class="in" id="member-role" name="role"><option value="controller" @selected(old('role', $editing->is_admin ? 'admin' : 'controller') === 'controller')>Contrôleur des billets</option><option value="admin" @selected(old('role', $editing->is_admin ? 'admin' : 'controller') === 'admin')>Administrateur</option></select></div>
<label class="sw"><span>Compte actif</span><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing->is_active))></label>
<div class="f"><label for="member-password">{{ $editing->exists ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe du membre' }}</label><input class="in" id="member-password" type="password" name="password" autocomplete="new-password" @required(!$editing->exists)></div>
<div class="f"><label for="member-password-confirmation">Confirmer ce mot de passe</label><input class="in" id="member-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
<p class="hint" style="line-height:1.8">Au moins 12 caractères : majuscule, minuscule, chiffre et symbole. Un compte désactivé ne peut plus accéder à l’espace équipe.</p>
<div class="f"><label for="member-authorization">Ton mot de passe pour autoriser la modification</label><input class="in" id="member-authorization" name="current_password" type="password" autocomplete="current-password" required></div>
<div class="act"><a class="btn" href="{{ route('admin.team.index') }}">Annuler</a><button class="btn" type="submit">Enregistrer</button></div>
</form></div></div>
@endif
@endsection
