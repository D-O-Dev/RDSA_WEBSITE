@extends('admin.layout')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
<div class="bar">
    <a class="btn pri" href="{{ route('admin.index', ['create' => 1]) }}"><i class="bi bi-plus-lg"></i>Nouvelle édition</a>
</div>
<div class="tw">
    <table>
        <thead>
            <tr>
                <th>Édition</th>
                <th>Date</th>
                <th>Lieu</th>
                <th>Publication</th>
                <th>Participations</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($editions as $item)
            <tr>
                <td><b>{{ $item->name }}</b>
                    <div class="hint">{{ $item->is_archived ? 'Archivée' : ($item->starts_at->isPast() ? 'Date passée' : 'À venir') }}</div>
                </td>
                <td>{{ $item->starts_at->locale('fr')->translatedFormat('d F Y') }}
                    <div class="hint">{{ $item->starts_at->format('H:i') }}</div>
                </td>
                <td>{{ $item->venue }}</td>
                <td><span class="bd {{ $item->is_published ? 'ok' : 'mu' }}">{{ $item->is_published ? 'Publiée' : 'Brouillon' }}</span>
                    <span class="bd {{ $item->is_active ? 'in2' : 'mu' }}">{{ $item->is_active ? 'Ouverte' : 'Fermée' }}</span>
                </td>
                <td>{{ $item->participations_count }}</td>
                <td><div class="act"><a class="btn sm" href="{{ route('admin.index', ['edit' => $item->id]) }}"><i class="bi bi-pencil"></i>Modifier</a><form method="POST" enctype="multipart/form-data" action="{{ route('admin.editions.duplicate', $item) }}">@csrf<button class="btn sm" type="submit">Dupliquer</button></form></div></td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="empty"><i class="bi bi-calendar-event"></i>Aucune édition enregistrée.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@section('admin-modal')
@if($editing)
<div class="ov on" id="ov">
    <div class="md" role="dialog" aria-modal="true" aria-labelledby="edition-title">
        <form method="POST" enctype="multipart/form-data" action="{{ $editing->exists ? route('admin.editions.update', $editing) : route('admin.editions.store') }}">
            @csrf
            @if($editing->exists) @method('PUT') @endif
            <div class="media-editor-heading"><h3 id="edition-title">{{ $editing->exists ? 'Modifier l’édition' : 'Nouvelle édition' }}</h3><a class="btn media-editor-close" href="{{ route('admin.index') }}" aria-label="Fermer le formulaire édition"><i class="bi bi-x-lg" aria-hidden="true"></i></a></div>
            @if($editing->exists)<a class="btn sm" href="{{ route('admin.appearance.index', ['edition' => $editing->id]) }}"><i class="bi bi-palette"></i>Design et apparence</a>@endif
            @if($errors->any())
            <div class="lock" role="alert">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
            @endif
            <div class="row2">
                <div class="f"><label for="edition-name">Nom de l’édition</label><input class="in" id="edition-name" name="name" value="{{ old('name', $editing->name) }}" maxlength="100" required autofocus></div>
                <div class="f"><label for="edition-number">Numéro</label><input class="in" id="edition-number" type="number" name="number" value="{{ old('number', $editing->number) }}" min="1" max="65535" required></div>
            </div>
            <fieldset class="card"><legend>Organisation des réservations</legend><p class="hint">Laisse vide pour garder une ouverture manuelle et aucune limite de places. Les dates utilisent l’heure du site.</p><div class="row2">@foreach(['reservations_open_at' => 'Ouvrir les réservations le', 'reservations_close_at' => 'Fermer les réservations le'] as $field => $label)<div class="f"><label for="{{ $field }}">{{ $label }}</label><input class="in" id="{{ $field }}" name="{{ $field }}" type="datetime-local" value="{{ old($field, $editing->$field?->format('Y-m-d\TH:i')) }}"></div>@endforeach</div><div class="f"><label for="edition-capacity">Nombre maximal de personnes</label><input class="in" id="edition-capacity" name="capacity" type="number" min="1" max="1000000" value="{{ old('capacity', $editing->capacity) }}"></div></fieldset>
            <div class="f"><label for="edition-theme">Thème du programme</label><input class="in" id="edition-theme" name="theme" value="{{ old('theme', $editing->theme) }}" maxlength="255" required></div>
            <div class="row2">
                <div class="f"><label for="edition-date">Date et heure</label><input class="in" id="edition-date" type="datetime-local" name="starts_at" value="{{ old('starts_at', $editing->starts_at?->format('Y-m-d\TH:i')) }}" required></div>
            </div>
            <div class="f"><label for="edition-venue">Lieu</label><input class="in" id="edition-venue" name="venue" value="{{ old('venue', $editing->venue) }}" maxlength="255" required></div>
            <div class="f"><label for="edition-address">Précisions sur le lieu</label><input class="in" id="edition-address" name="venue_details" value="{{ old('venue_details', $editing->venue_details) }}" maxlength="255"></div>
            <div class="row2">
                <div class="f"><label for="edition-contact-phone">Téléphone de contact</label><input class="in" id="edition-contact-phone" name="contact_phone" type="tel" maxlength="30" value="{{ old('contact_phone', $editing->contact_phone) }}" placeholder="+225 …"></div>
                <div class="f"><label for="edition-contact-whatsapp">WhatsApp de contact</label><input class="in" id="edition-contact-whatsapp" name="contact_whatsapp" type="tel" maxlength="30" value="{{ old('contact_whatsapp', $editing->contact_whatsapp) }}" placeholder="+225 …"></div>
            </div>
            <div class="f"><label for="edition-map">Lien de localisation Google Maps</label><input class="in" id="edition-map" name="map_url" type="url" maxlength="2000" value="{{ old('map_url', $editing->map_url) }}" placeholder="https://www.google.com/maps/…"><div class="hint">Lien de la position exacte ou URL d’intégration Google Maps (sans code iframe). Sans lien précis, la carte recherche le nom et l’adresse du lieu. Les liens courts sont résolus automatiquement si Google le permet. L’URL d’intégration ou les coordonnées garantissent le repère dans la carte.</div></div>
            <div class="row2"><div class="f"><label for="edition-lat">Latitude</label><input class="in" id="edition-lat" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude', $editing->latitude) }}"></div><div class="f"><label for="edition-lng">Longitude</label><input class="in" id="edition-lng" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude', $editing->longitude) }}"></div></div>
            <div class="hint">Facultatives si le lien contient déjà le repère. Dans Google Maps, clique sur le lieu avec le bouton droit pour copier ses coordonnées. Elles sont prioritaires pour le repère et l’itinéraire.</div>

            @php($mapPreview = \App\Support\EditionLocation::data($editing))
            @if($mapPreview && $mapPreview['embed'])<div class="f"><label>Aperçu du repère enregistré</label><iframe src="{{ $mapPreview['embed'] }}" title="Carte du lieu de cette édition" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="display:block;width:100%;height:300px;border:0;border-radius:14px" allowfullscreen></iframe></div>@endif
            <div class="f"><label for="edition-description">Description</label><textarea class="in" id="edition-description" name="description" rows="3" maxlength="10000">{{ old('description', $editing->description) }}</textarea></div>
            @foreach(['hero_background_path' => 'Image de fond du hero'] as $field => $label)
            <div class="f"><label for="{{ $field }}">{{ $label }}</label>@include('admin.partials.image-source', ['inputId' => $field, 'inputName' => $field, 'imageValue' => old($field, $editing->$field)])</div>
            @endforeach
            @if($editing->exists)<div class="content-program-note"><i class="bi bi-palette"></i><p>Les modèles, les images et les animations se règlent dans <a href="{{ route('admin.appearance.index', ['edition' => $editing->id]) }}">Design et apparence</a>, avec un aperçu avant enregistrement.</p></div>@else<p class="hint">Après création, retrouve cette édition dans Design et apparence pour personnaliser tout le site.</p>@endif
            <div class="hint">Ouvrir cette édition ferme les inscriptions des autres éditions.</div>
            @foreach(['is_published' => 'Publier l’édition', 'is_active' => 'Ouvrir les inscriptions', 'is_archived' => 'Archiver l’édition'] as $field => $label)
            <label class="sw"><span>{{ $label }}</span><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $editing->$field))></label>
            @endforeach
            <div class="act"><a class="btn" href="{{ route('admin.index') }}">Annuler</a><button class="btn pri" type="submit">Enregistrer</button></div>
        </form>
        @if($editing->exists) @can('manage-sensitive-data')<details class="update-delete"><summary>Supprimer ce brouillon</summary><p class="hint">Possible uniquement pour un brouillon sans réservation ni contenu associé.</p><form method="POST" enctype="multipart/form-data" action="{{ route('admin.editions.destroy', $editing) }}">@csrf @method('DELETE')<div class="f"><label>Recopie {{ $editing->name }}<input class="in" name="confirmation_name" required></label></div><div class="f"><label>Ton mot de passe<input class="in" name="current_password" type="password" autocomplete="current-password" required></label></div><button class="btn bad" type="submit">Supprimer le brouillon</button></form></details>@endcan @endif

    </div>
</div>
@endif
@endsection
