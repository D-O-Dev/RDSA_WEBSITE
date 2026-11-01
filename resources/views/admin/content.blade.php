@extends('admin.layout')
@section('admin-title', 'Contenu du site')
@section('admin-content')
<div class="content-manager">
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="lock" role="alert">Certaines modifications n’ont pas été enregistrées. Vérifie les champs signalés ci-dessous.</div>@endif
<div class="content-toolbar">
<div><h2>Personnalisez votre site</h2><p>Ouvrez une rubrique, modifiez ses textes, puis enregistrez.</p></div>
<button class="btn" type="submit" form="site-content-form"><i class="bi bi-check2" aria-hidden="true"></i>Enregistrer</button>
</div>
<div class="content-program-note"><i class="bi bi-calendar3" aria-hidden="true"></i><p>Pour changer la date, le lieu, le thème ou la carte de la rencontre, rendez-vous dans <a href="{{ route('admin.index') }}">Éditions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>.</p></div>
<form id="site-content-form" method="POST" enctype="multipart/form-data" action="{{ route('admin.content.update') }}">
@csrf @method('PUT')
<div class="content-sections">
@foreach($groups as $groupKey => $group)
@php($hasErrors = collect(array_keys($group['fields']))->contains(fn ($key) => $errors->has($key)))
<details class="content-section" @if($loop->first || $hasErrors) open @endif>
<summary><span class="content-section-icon"><i class="bi bi-{{ $group['icon'] }}" aria-hidden="true"></i></span><span class="content-section-label"><strong>{{ $group['label'] }}</strong><span>{{ $group['description'] }}</span></span><i class="bi bi-chevron-down content-section-arrow" aria-hidden="true"></i></summary>
<div class="content-section-body">
@if($groupKey === 'hero')
<p class="content-section-help">Ces textes apparaissent sur la grande image d’accueil. Le nom de l’édition, son thème et la date se règlent dans Éditions.</p>
@endif
@if($groupKey === 'values')
<div class="content-values-grid">
@for($number = 1; $number <= 4; $number++)
<fieldset class="content-value-group"><legend><span>{{ $number }}</span>{{ ['Un seul but', 'Adoration', 'Enseignements', 'Impact'][$number - 1] }}</legend>
@foreach(['title', 'text'] as $suffix)
@php($key = 'value_' . $number . '_' . $suffix)
@include('admin.content.field', ['field' => $group['fields'][$key]])
@endforeach
</fieldset>
@endfor
</div>
@else
@foreach($group['fields'] as $key => $field)
@include('admin.content.field')
@endforeach
@endif
</div>
</details>
@endforeach
</div>
<div class="content-save"><span>Les modifications apparaîtront sur le site après l’enregistrement.</span><button class="btn" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>Enregistrer les modifications</button></div>
</form>
</div>
@endsection
