@extends('admin.layout')
@section('admin-title', 'Calendrier')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any() && !$editing)<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<div class="calendar-intro"><div><h2>Le programme de l’équipe</h2><p>Retrouve les éditions et organise les préparatifs. Les rendez-vous internes restent privés.</p></div><a class="btn" href="{{ route('admin.calendar.index', ['month' => $month->format('Y-m'), 'create' => 1]) }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>Ajouter un rendez-vous</a></div>
<section class="card calendar-panel" aria-label="Calendrier mensuel">
<div class="calendar-toolbar">
<div class="calendar-nav">
@if($month->format('Y-m') > '1900-01')<a class="ib" href="{{ route('admin.calendar.index', ['month' => $month->subMonth()->format('Y-m')]) }}" aria-label="Mois précédent"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>@endif
<h3 id="calendar-month-label" tabindex="-1">{{ $month->locale('fr')->translatedFormat('F Y') }}</h3>
@if($month->format('Y-m') < '2199-12')<a class="ib" href="{{ route('admin.calendar.index', ['month' => $month->addMonth()->format('Y-m')]) }}" aria-label="Mois suivant"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>@endif
</div>
<form class="calendar-month-form" method="GET" action="{{ route('admin.calendar.index') }}"><label for="calendar-month">Choisir un mois</label><input class="in" type="month" name="month" id="calendar-month" value="{{ $month->format('Y-m') }}" min="1900-01" max="2199-12" required><button class="btn instant-fallback" type="submit">Afficher</button></form>
<div class="calendar-nav"><a class="btn sm" href="{{ route('admin.calendar.index') }}">Ce mois-ci</a></div>
</div>
<div class="calendar-legend"><span><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $editions->count() }} {{ $editions->count() === 1 ? 'édition' : 'éditions' }}</span><span><i class="bi bi-people" aria-hidden="true"></i>{{ $events->count() }} rendez-vous internes</span></div>
<div class="calendar-grid" aria-labelledby="calendar-month-label">
@foreach(['Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.', 'Dim.'] as $weekday)<div class="calendar-weekday">{{ $weekday }}</div>@endforeach
@foreach($days as $day)
@php($items = $agenda->get($day->format('Y-m-d'), collect()))
@php($withinMonth = $day->format('Y-m') === $month->format('Y-m'))
@php($canSchedule = $day->year >= 1900 && $day->year < 2200)
<div class="calendar-day {{ !$withinMonth ? 'calendar-outside' : '' }} {{ $day->isToday() ? 'calendar-today' : '' }}" data-calendar-date="{{ $day->format('Y-m-d') }}">
<div class="calendar-day-top">@if($canSchedule)<a class="calendar-day-number" href="{{ $items->isNotEmpty() ? '#agenda-' . $day->format('Y-m-d') : route('admin.calendar.index', ['month' => $day->format('Y-m'), 'date' => $day->format('Y-m-d'), 'create' => 1]) }}" aria-label="{{ $day->locale('fr')->translatedFormat('l d F Y') }}{{ $items->isNotEmpty() ? ' : voir le programme' : ' : ajouter un rendez-vous' }}" @if($day->isToday()) aria-current="date" @endif>{{ $day->day }}</a><a class="calendar-day-add" href="{{ route('admin.calendar.index', ['month' => $day->format('Y-m'), 'date' => $day->format('Y-m-d'), 'create' => 1]) }}" aria-label="Ajouter un rendez-vous le {{ $day->format('d/m/Y') }}"><i class="bi bi-plus" aria-hidden="true"></i></a>@else<span class="calendar-day-number">{{ $day->day }}</span>@endif</div>
<div class="calendar-day-items">
@foreach($items->take(2) as $item)<a href="{{ $item['url'] }}" class="calendar-chip {{ $item['kind'] === 'edition' ? 'calendar-edition-chip' : '' }}" title="{{ $item['starts_at']->format('H:i') }} · {{ $item['title'] }}"><span>{{ $item['starts_at']->format('H:i') }}</span>{{ $item['title'] }}</a>@endforeach
@if($items->count() > 2)<a class="calendar-more" href="#agenda-{{ $day->format('Y-m-d') }}">+ {{ $items->count() - 2 }} autres</a>@endif
</div>
@if($items->isNotEmpty())<a class="calendar-mobile-count" href="#agenda-{{ $day->format('Y-m-d') }}" aria-label="Voir {{ $items->count() }} {{ $items->count() > 1 ? 'éléments' : 'élément' }} le {{ $day->format('d/m/Y') }}">{{ $items->count() }}</a>@endif
</div>
@endforeach
</div>
</section>
<section class="calendar-agenda" aria-labelledby="calendar-agenda-title"><div class="calendar-agenda-heading"><h2 id="calendar-agenda-title">Au programme ce mois-ci</h2><a class="btn sm" href="{{ route('admin.index', ['create' => 1]) }}">Créer une édition <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
@forelse($agenda as $date => $items)
<div id="agenda-{{ $date }}" class="calendar-agenda-day"><h3>{{ \Carbon\CarbonImmutable::parse($date)->locale('fr')->translatedFormat('l d F') }}</h3>
@foreach($items as $item)
<article class="card calendar-agenda-item"><time datetime="{{ $item['starts_at']->toIso8601String() }}">{{ $item['starts_at']->format('H:i') }}</time><div class="calendar-agenda-content"><span class="calendar-item-type">{{ $item['kind'] === 'edition' ? 'ÉDITION RDSA' : 'RENDEZ-VOUS INTERNE' }}</span><h4>{{ $item['title'] }}</h4>@if($item['location'])<p><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $item['location'] }}</p>@endif @if($item['notes'])<p class="calendar-notes">{{ $item['notes'] }}</p>@endif @if($item['kind'] === 'edition')<span class="bd mu">{{ $item['state'] }}</span>@endif</div><a class="btn sm" href="{{ $item['url'] }}" aria-label="Modifier {{ $item['title'] }}">Modifier <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></article>
@endforeach
</div>
@empty<div class="card calendar-empty"><i class="bi bi-calendar3" aria-hidden="true"></i><h3>Le mois est encore libre</h3><p>Ajoute une édition ou un rendez-vous pour organiser la préparation.</p></div>@endforelse
</section>
@endsection
@section('admin-modal')
@if($editing)
<div class="ov on"><div class="md calendar-editor" role="dialog" aria-modal="true" aria-labelledby="calendar-editor-title">
<form method="POST" action="{{ $editing->exists ? route('admin.calendar.update', $editing) : route('admin.calendar.store') }}">
@csrf @if($editing->exists) @method('PUT') @endif
<div class="media-editor-heading"><h3 id="calendar-editor-title">{{ $editing->exists ? 'Modifier le rendez-vous' : 'Ajouter un rendez-vous' }}</h3><a class="btn media-editor-close" href="{{ route('admin.calendar.index', ['month' => $month->format('Y-m')]) }}" aria-label="Fermer le formulaire rendez-vous"><i class="bi bi-x-lg" aria-hidden="true"></i></a></div>
<p class="hint calendar-editor-note">Ce rendez-vous est visible uniquement par les administrateurs.</p>
@if($errors->any())<div class="lock" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="f"><label for="calendar-title">Nom du rendez-vous</label><input class="in" id="calendar-title" name="title" value="{{ old('title', $editing->title) }}" placeholder="Ex. : Réunion de préparation" maxlength="150" required></div>
<div class="f"><label for="calendar-date">Date et heure</label><input class="in" id="calendar-date" name="starts_at" type="datetime-local" value="{{ old('starts_at', $editing->starts_at?->format('Y-m-d\TH:i')) }}" min="1900-01-01T00:00" max="2199-12-31T23:59" required></div>
<div class="f"><label for="calendar-location">Lieu (facultatif)</label><input class="in" id="calendar-location" name="location" value="{{ old('location', $editing->location) }}" maxlength="255"></div>
<div class="f"><label for="calendar-notes">Informations pour l’équipe (facultatif)</label><textarea class="in" id="calendar-notes" name="notes" rows="3" maxlength="3000">{{ old('notes', $editing->notes) }}</textarea></div>
<div class="act"><a class="btn" href="{{ route('admin.calendar.index', ['month' => $month->format('Y-m')]) }}">Annuler</a><button class="btn" type="submit">Enregistrer</button></div>
</form>
@if($editing->exists)
<details class="calendar-delete"><summary>Retirer ce rendez-vous</summary><p class="hint">Il ne sera plus affiché dans le calendrier. Les éditions et les réservations ne seront pas modifiées.</p><form method="POST" action="{{ route('admin.calendar.destroy', $editing) }}">@csrf @method('DELETE')<button class="btn bad" type="submit">Confirmer le retrait</button></form></details>
@endif
</div></div>
@endif
@endsection
