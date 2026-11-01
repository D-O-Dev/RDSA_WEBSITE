@php($template = $heroDesign['template'])
@php($heroForeground = ($heroSettings['foreground'] ?? null) ?: $heroSlides->first())
<section id="accueil" class="rdsa-hero reference-hero ref-{{ $template }} hero-model-{{ $heroDesign['layout'] }} hero-font-{{ $heroSettings['font'] ?? 'original' }}" data-hero-mode="{{ $heroSettings['mode'] ?? 'zoom' }}" data-hero-interval="{{ $heroSettings['interval'] ?? 6 }}" data-reference-layout="{{ $template }}" data-ref-align="{{ $heroSettings['position'] ?? 'left' }}" style="--hero-accent:{{ $heroDesign['accent'] }};--hero-overlay:{{ ($heroSettings['overlay'] ?? 55) / 100 }}">
<div class="rdsa-hero-background" aria-hidden="true">@foreach($heroSlides as $heroSlide)<img class="hero-slide {{ $loop->first ? 'is-active' : '' }}" src="{{ asset($heroSlide) }}" alt="" @if(!$loop->first) loading="lazy" @endif>@endforeach</div><div class="ref-shade" aria-hidden="true"></div>
<div class="container ref-shell">
@if($template === 'cloud')
<div class="ref-cloud-heading">@include('hero.title')</div><div class="ref-cloud-dashboard"><div class="ref-cloud-main ref-glass">@include('hero.countdown')</div><div class="ref-cloud-side ref-glass">@include('hero.event')</div></div>@include('hero.values')
@elseif($template === 'voyage')
<div class="ref-voyage-layout"><div class="ref-copy">@include('hero.title')@include('hero.event')</div><div class="ref-destination-deck">@foreach($heroSlides->take(4) as $heroSlide)<button type="button" class="ref-destination {{ $loop->first ? 'is-active' : '' }}" data-hero-select="{{ $loop->index }}" aria-label="Afficher la photographie {{ $loop->iteration }}"><img src="{{ asset($heroSlide) }}" alt="Photographie {{ $loop->iteration }} de {{ $edition?->name ?? 'la RDSA' }}"><span><small>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</small><strong>{{ $edition?->venue ?? 'RDSA' }}</strong></span></button>@endforeach</div></div><div class="ref-voyage-footer"><span class="ref-slide-position" data-hero-position>01</span><span class="ref-voyage-line"></span><span>{{ str_pad((string) $heroSlides->count(), 2, '0', STR_PAD_LEFT) }}</span><span class="ref-voyage-time">{{ $edition?->starts_at?->format('d.m.Y') }}</span></div>
@elseif($template === 'immersion')
<div class="ref-immersion-heading">@include('hero.title')</div><div class="ref-immersion-panels"><div class="ref-glass">@include('hero.event')</div><div class="ref-glass">@include('hero.countdown')</div></div>@include('hero.values')
@else
<div class="ref-main-grid"><div class="ref-copy">@include('hero.title')@if(!in_array($template, ['horizon', 'harvest']))@include('hero.event')@endif</div>
<div class="ref-visual">
@if(in_array($template, ['aerial', 'atelier', 'lilac']))
<figure class="ref-foreground {{ $template === 'atelier' ? 'ref-screen' : '' }}">@if($template === 'atelier')<figcaption><span>RDSA</span><i></i><i></i><i></i></figcaption>@endif<img @if(!($heroSettings['foreground'] ?? null)) data-hero-foreground @endif src="{{ asset($heroForeground) }}" alt="Photographie de {{ $edition?->name ?? 'la RDSA' }}"></figure>
<div class="ref-floating-note ref-glass"><i class="bi bi-geo-alt" aria-hidden="true"></i><div><small>ON SE RETROUVE À</small><strong>{{ $edition?->venue ?? 'Lieu à confirmer' }}</strong><span>{{ $edition?->starts_at?->locale('fr')->translatedFormat('d F Y') }}</span></div></div>
@if($template === 'lilac')<div class="ref-lilac-chips">@foreach(['music-note-beamed' => 'Adoration', 'book' => 'Enseignement', 'heart' => 'Communion'] as $icon => $text)<span><i class="bi bi-{{ $icon }}" aria-hidden="true"></i>{{ $text }}</span>@endforeach</div>@endif
@elseif($template === 'horizon')
<div class="ref-horizon-panel ref-glass"><div class="ref-panel-heading"><span>{{ $edition?->name ?? 'RDSA' }}</span><span>PROCHAINE ÉDITION</span></div>@include('hero.countdown')@include('hero.event')<div class="ref-entry-note"><i class="bi bi-qr-code" aria-hidden="true"></i><span>Ton billet personnel.<br>Un QR, une personne, une entrée.</span></div></div>
@elseif($template === 'harvest')
<div class="ref-harvest-panel ref-glass">@include('hero.countdown')@include('hero.event')</div>
@elseif($template === 'sanctuary')
<div class="ref-sanctuary-note"><span>UN MOMENT À PART.</span><p>La prière.<br>L’adoration.<br>Ensemble.</p></div>
@endif
</div></div>
@include('hero.values')
@if(in_array($template, ['aerial', 'atelier', 'lilac', 'sanctuary']))<div class="ref-bottom-countdown">@include('hero.countdown')</div>@endif
@endif
</div>
@if($heroSlides->count() > 1)<div class="hero-slide-controls"><button type="button" data-hero-prev aria-label="Image précédente">‹</button><button type="button" data-hero-pause aria-label="Mettre en pause le diaporama">Ⅱ</button><button type="button" data-hero-next aria-label="Image suivante">›</button></div>@endif
</section>
