@php($designSettings = $heroSettings['design'] ?? [])
<section class="appearance-models" data-design-picker>
<h2>Choisir un modèle</h2><p class="hint">Des compositions adaptées à la RDSA à partir de vos neuf références. Les photographies affichées sur le site seront celles de l’édition.</p>
<div class="design-options">
@foreach(collect(config('site_designs'))->filter(fn ($model, $key) => !($model['legacy'] ?? false) || $key === ($designSettings['preset'] ?? 'original')) as $preset => $model)
<label class="design-option"><input type="radio" name="hero_settings[design][preset]" value="{{ $preset }}" @checked(($designSettings['preset'] ?? 'original') === $preset) data-design-preset data-primary="{{ $model['primary'] }}" data-accent="{{ $model['accent'] }}" data-background="{{ $model['background'] }}">
@if(isset($model['reference']))<span class="appearance-reference"><img src="{{ asset('design-references/' . $model['reference'] . ($model['reference'] === '09' ? '.png' : '.jpg')) }}" alt="Référence visuelle {{ $model['reference'] }} du modèle {{ $model['name'] }}" loading="lazy"><small>Réf. {{ $model['reference'] }}</small></span>
@else<span class="appearance-original" aria-hidden="true"><b>RDSA</b><span>Votre style d’origine</span></span>@endif
<strong>{{ $model['name'] }}</strong><span>{{ $model['description'] }}</span></label>
@endforeach
</div>
<details class="appearance-options"><summary>Couleurs, matières et formes</summary>
<div class="row2">
@foreach(['layout' => ['Composition', ['auto' => 'Suivre le modèle', 'aerial' => 'Élan · image aérienne', 'atelier' => 'Atelier · photographie éditoriale', 'horizon' => 'Horizon · panneau de verre', 'sanctuary' => 'Sanctuaire · immersion sombre', 'harvest' => 'Espérance · panorama', 'cloud' => 'Nuage · verre clair', 'lilac' => 'Lilas · image centrale', 'immersion' => 'Immersion · titre centré', 'voyage' => 'Voyage · cartes défilantes', 'original' => 'RDSA original']], 'effect' => ['Matière', ['auto' => 'Suivre le modèle', 'solid' => 'Surface pleine', 'glass' => 'Verre transparent', 'frosted' => 'Verre dépoli', 'liquid' => 'Verre liquide']], 'radius' => ['Formes', ['auto' => 'Suivre le modèle', 'rounded' => 'Très arrondies', 'soft' => 'Angles doux', 'square' => 'Angles nets']], 'motion' => ['Mouvements', ['gentle' => 'Doux', 'expressive' => 'Expressifs', 'none' => 'Désactivés']]] as $option => [$label, $choices])
<div class="f"><label for="design-{{ $option }}">{{ $label }}</label><select class="in" id="design-{{ $option }}" name="hero_settings[design][{{ $option }}]">@foreach($choices as $value => $text)<option value="{{ $value }}" @selected(($designSettings[$option] ?? ($option === 'motion' ? 'gentle' : 'auto')) === $value)>{{ $text }}</option>@endforeach</select></div>
@endforeach
</div><label class="sw"><span>Choisir mes propres couleurs</span><input type="hidden" name="hero_settings[design][custom_colors]" value="0"><input type="checkbox" name="hero_settings[design][custom_colors]" value="1" data-design-colors @checked($designSettings['custom_colors'] ?? false)></label>
<div class="design-color-fields row2">@foreach(['primary' => 'Principale', 'accent' => 'Accent', 'background' => 'Fond du site'] as $color => $label)<div class="f"><label for="design-color-{{ $color }}">{{ $label }}</label><input class="in" id="design-color-{{ $color }}" type="color" name="hero_settings[design][{{ $color }}]" data-design-color="{{ $color }}" value="{{ $designSettings[$color] ?? config('site_designs.' . ($designSettings['preset'] ?? 'original') . '.' . $color, '#07152f') }}"></div>@endforeach</div>
</details></section>
