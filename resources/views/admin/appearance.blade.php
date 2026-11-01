@extends('admin.layout')
@section('admin-title', 'Design et apparence')
@section('admin-content')
<div class="appearance-heading"><div><span class="appearance-eyebrow">LE STUDIO RDSA</span><h2>Une édition, une nouvelle ambiance.</h2><p>Choisis un modèle, ajuste tes images et regarde le résultat avant de l’enregistrer.</p></div>@if($editing)<button class="btn" type="submit" form="appearance-form"><i class="bi bi-check2"></i>Enregistrer l’apparence</button>@endif</div>
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<form class="bar" method="GET" action="{{ route('admin.appearance.index') }}"><label for="appearance-edition">Édition à personnaliser</label><select class="sel" name="edition" id="appearance-edition">@foreach($editions as $edition)<option value="{{ $edition->id }}" @selected($editing?->id === $edition->id)>{{ $edition->name }}{{ $edition->is_active ? ' · en cours' : ($edition->is_published ? '' : ' · brouillon') }}</option>@endforeach</select><button class="btn" type="submit">Choisir</button></form>
@if(!$editing)<div class="card empty">Crée d’abord une édition pour lui associer une apparence. <a class="btn" href="{{ route('admin.index', ['create' => 1]) }}">Créer une édition</a></div>
@else
@php($heroSettings = old('hero_settings', $editing->hero_settings ?? []))
<form id="appearance-form" class="appearance-workspace" method="POST" action="{{ route('admin.appearance.update', $editing) }}" data-preview-url="{{ route('admin.appearance.preview', $editing) }}">@csrf @method('PUT')
<div class="appearance-controls">
@include('admin.partials.design-picker')
<details class="appearance-options"><summary>Photographies et arrière-plan</summary>
<div class="f"><label for="appearance-background">Image principale de l’édition</label>@include('admin.partials.image-source', ['inputId' => 'appearance-background', 'inputName' => 'hero_background_path', 'imageValue' => old('hero_background_path', $editing->hero_background_path)])</div>
<div class="f"><label for="appearance-foreground">Image mise en avant (facultatif)</label>@include('admin.partials.image-source', ['inputId' => 'appearance-foreground', 'inputName' => 'hero_settings[foreground]', 'imageValue' => $heroSettings['foreground'] ?? ''])<p class="hint">Une photo de l’événement ou un portrait PNG/WebP transparent. Sinon, le modèle utilise l’image principale.</p></div>
@for($slide = 0; $slide < 6; $slide++)<div class="f"><label for="appearance-slide-{{ $slide }}">Autre photographie {{ $slide + 1 }}</label><input class="in" id="appearance-slide-{{ $slide }}" name="hero_settings[slides][]" value="{{ $heroSettings['slides'][$slide] ?? '' }}" maxlength="2000" placeholder="https://…/image.jpg"></div>@endfor
</details>
<details class="appearance-options"><summary>Animation et typographie</summary><div class="row2">
@foreach(['mode' => ['Images', ['static' => 'Image fixe', 'fade' => 'Fondu', 'slide' => 'Glissement', 'zoom' => 'Zoom doux']], 'font' => ['Titres du hero', ['original' => 'Selon le modèle', 'classic' => 'Classiques', 'modern' => 'Modernes']], 'position' => ['Alignement', ['left' => 'Selon le modèle', 'center' => 'Centré', 'right' => 'À droite']], 'interval' => ['Durée par image', [4 => '4 secondes', 6 => '6 secondes', 8 => '8 secondes', 10 => '10 secondes', 15 => '15 secondes', 20 => '20 secondes']]] as $key => [$label, $choices])<div class="f"><label for="appearance-{{ $key }}">{{ $label }}</label><select class="in" id="appearance-{{ $key }}" name="hero_settings[{{ $key }}]">@foreach($choices as $value => $text)<option value="{{ $value }}" @selected((string) ($heroSettings[$key] ?? ['mode' => 'zoom', 'font' => 'original', 'position' => 'left', 'interval' => 6][$key]) === (string) $value)>{{ $text }}</option>@endforeach</select></div>@endforeach
<div class="f"><label for="appearance-overlay">Voile sombre (20 à 85 %)</label><input class="in" id="appearance-overlay" name="hero_settings[overlay]" type="number" required min="20" max="85" value="{{ $heroSettings['overlay'] ?? 55 }}"></div></div>
</details>
<div class="appearance-savebar"><span>Les inscriptions, dates et lieux restent ceux de l’édition.</span><button class="btn sm" type="button" data-scroll-preview><i class="bi bi-eye"></i>Aperçu</button><button class="btn" type="submit"><i class="bi bi-check2"></i>Enregistrer</button></div>
</div>
<section class="appearance-preview"><div class="appearance-preview-heading"><div><strong>Aperçu du site</strong><span>Les changements ne sont publiés qu’après enregistrement.</span></div><div class="appearance-devices"><button class="btn sm" type="button" data-preview-device="1280" aria-pressed="true" aria-label="Aperçu ordinateur"><i class="bi bi-display"></i></button><button class="btn sm" type="button" data-preview-device="390" aria-pressed="false" aria-label="Aperçu téléphone"><i class="bi bi-phone"></i></button><button class="btn sm" type="button" data-refresh-preview aria-label="Actualiser l’aperçu"><i class="bi bi-arrow-clockwise"></i></button></div></div>
<p class="hint" data-preview-feedback role="status">Préparation de l’aperçu…</p><div class="appearance-preview-stage"><iframe title="Aperçu de l’apparence RDSA" data-appearance-preview sandbox="allow-scripts allow-same-origin" referrerpolicy="same-origin"></iframe></div><noscript><p>Active JavaScript pour consulter l’aperçu.</p></noscript></section>
</form>
@endif
@endsection
