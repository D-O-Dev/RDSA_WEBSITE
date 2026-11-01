@extends('admin.layout')
@section('admin-title', 'Annonces et FAQ')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any() && !$editing)<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<div class="updates-heading"><div><h2>Informer et répondre aux visiteurs</h2><p>Les contenus publiés se consultent via le bouton de contact en bas à droite du site.</p></div><a class="btn" href="{{ route('admin.updates.index', ['kind' => $kind, 'create' => $kind]) }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>{{ $kind === 'faq' ? 'Ajouter une question' : 'Ajouter une annonce' }}</a></div>
<nav class="updates-tabs" aria-label="Type de contenu"><a class="btn {{ $kind === 'announcement' ? 'updates-tab-active' : '' }}" href="{{ route('admin.updates.index', ['kind' => 'announcement']) }}" @if($kind === 'announcement') aria-current="page" @endif><i class="bi bi-megaphone" aria-hidden="true"></i>Annonces</a><a class="btn {{ $kind === 'faq' ? 'updates-tab-active' : '' }}" href="{{ route('admin.updates.index', ['kind' => 'faq']) }}" @if($kind === 'faq') aria-current="page" @endif><i class="bi bi-question-circle" aria-hidden="true"></i>Questions fréquentes</a></nav>
<form class="bar" method="GET" action="{{ route('admin.updates.index') }}"><input type="hidden" name="kind" value="{{ $kind }}"><div class="srch"><i class="bi bi-search" aria-hidden="true"></i><input name="q" value="{{ request('q') }}" maxlength="150" placeholder="{{ $kind === 'faq' ? 'Rechercher une question ou une réponse' : 'Rechercher une annonce' }}" aria-label="Rechercher un contenu"></div><button class="btn" type="submit">Rechercher</button></form>
<div class="updates-list">
@forelse($items as $item)
@php($publication = $item->publicationLabel())
<article class="card update-card"><div class="update-card-heading"><span class="update-kind-icon"><i class="bi bi-{{ $kind === 'faq' ? 'question-circle' : 'megaphone' }}" aria-hidden="true"></i></span><div><h3>{{ $item->title }}</h3><p>{{ $item->edition?->name ?? 'Toutes les éditions' }} · Ordre {{ $item->sort_order }}</p></div><span class="bd {{ $publication === 'Publié' ? 'ok' : ($publication === 'Programmé' ? 'wa' : 'mu') }}">{{ $publication }}</span></div><p class="update-body-preview">{{ $item->body }}</p>
@if($item->starts_at || $item->ends_at)<p class="hint update-dates">@if($item->starts_at) Du {{ $item->starts_at->format('d/m/Y à H:i') }} @else Dès la publication @endif @if($item->ends_at) · Jusqu’au {{ $item->ends_at->format('d/m/Y à H:i') }} @endif</p>@endif
<div class="update-card-actions"><form method="POST" data-instant-action action="{{ route('admin.updates.publish', $item) }}">@csrf @method('PATCH')<label class="sw"><span>Afficher sur le site</span><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked($item->is_published) aria-label="Publier {{ $item->title }}"><button class="btn sm instant-fallback" type="submit">Appliquer</button></label></form><a class="btn sm" href="{{ route('admin.updates.index', ['kind' => $kind, 'edit' => $item->id]) }}"><i class="bi bi-pencil" aria-hidden="true"></i>Modifier</a></div></article>
@empty<div class="card updates-empty"><i class="bi bi-{{ $kind === 'faq' ? 'question-circle' : 'megaphone' }}" aria-hidden="true"></i><h3>{{ $kind === 'faq' ? 'Aucune question fréquente pour le moment' : 'Aucune annonce pour le moment' }}</h3><p>{{ request('q') ? 'Essaie une autre recherche.' : 'Ajoute un contenu, puis publie-le quand il est prêt.' }}</p></div>@endforelse
</div>
@include('admin.partials.pagination', ['results' => $items])
@endsection
@section('admin-modal')
@if($editing)
<div class="ov on"><div class="md updates-editor" role="dialog" aria-modal="true" aria-labelledby="update-editor-title"><form method="POST" action="{{ $editing->exists ? route('admin.updates.update', $editing) : route('admin.updates.store') }}">
@csrf @if($editing->exists) @method('PUT') @endif
<input type="hidden" name="kind" value="{{ $editing->kind }}">
<div class="media-editor-heading"><h3 id="update-editor-title">{{ $editing->kind === 'faq' ? ($editing->exists ? 'Modifier la question' : 'Ajouter une question') : ($editing->exists ? 'Modifier l’annonce' : 'Ajouter une annonce') }}</h3><a class="btn media-editor-close" href="{{ route('admin.updates.index', ['kind' => $editing->kind]) }}" aria-label="Fermer le formulaire"><i class="bi bi-x-lg" aria-hidden="true"></i></a></div>
@if($errors->any())<div class="lock" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="f"><label for="update-title">{{ $editing->kind === 'faq' ? 'Question du visiteur' : 'Titre de l’annonce' }}</label><input class="in" id="update-title" name="title" value="{{ old('title', $editing->title) }}" maxlength="150" required></div>
<div class="f"><label for="update-body">{{ $editing->kind === 'faq' ? 'Réponse de l’équipe' : 'Message à afficher' }}</label><textarea class="in" id="update-body" name="body" rows="5" maxlength="5000" required>{{ old('body', $editing->body) }}</textarea></div>
<div class="f"><label for="update-edition">Pour quelle édition ?</label><select class="in" id="update-edition" name="edition_id"><option value="">Toutes les éditions</option>@foreach($editions as $edition)<option value="{{ $edition->id }}" @selected((string) old('edition_id', $editing->edition_id) === (string) $edition->id)>{{ $edition->name }}</option>@endforeach</select><p class="hint">Un contenu lié à une édition n’apparaît que lorsque cette édition est ouverte et publiée.</p></div>
@if($editing->kind === 'announcement')
<fieldset class="update-schedule"><legend>Dates d’affichage (facultatif)</legend><div class="f"><label for="update-start">Commencer l’affichage le</label><input class="in" id="update-start" name="starts_at" type="datetime-local" value="{{ old('starts_at', $editing->starts_at?->format('Y-m-d\TH:i')) }}"></div><div class="f"><label for="update-end">Arrêter l’affichage le</label><input class="in" id="update-end" name="ends_at" type="datetime-local" value="{{ old('ends_at', $editing->ends_at?->format('Y-m-d\TH:i')) }}"></div><p class="hint">Laisse ces dates vides pour afficher l’annonce dès sa publication, sans date de fin.</p></fieldset>
@endif
<div class="f"><label for="update-order">Ordre d’affichage</label><input class="in" id="update-order" name="sort_order" type="number" min="0" max="100000" value="{{ old('sort_order', $editing->sort_order) }}" required><p class="hint">Les plus petits nombres apparaissent en premier.</p></div>
<label class="sw"><span>Afficher sur le site</span><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $editing->is_published))></label>
<div class="act"><a class="btn" href="{{ route('admin.updates.index', ['kind' => $editing->kind]) }}">Annuler</a><button class="btn" type="submit">Enregistrer</button></div>
</form>
@if($editing->exists)<details class="update-delete"><summary>Retirer ce contenu</summary><p class="hint">Il ne sera plus affiché sur le site ni dans cette liste.</p><form method="POST" action="{{ route('admin.updates.destroy', $editing) }}">@csrf @method('DELETE')<button class="btn bad" type="submit">Confirmer le retrait</button></form></details>@endif
</div></div>
@endif
@endsection
