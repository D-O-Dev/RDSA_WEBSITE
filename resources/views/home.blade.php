@extends('layouts.app')

@section('title', 'RDSA - Réunion des Assoiffés de Prière & d’Adoration')

@section('content')

<header class="site-header">

    <nav class="navbar navbar-expand-lg rdsa-navbar">
        <div class="container">

            <a class="navbar-brand rdsa-logo" href="#accueil">
                <img
                    src="{{ asset($siteContent['logo_path']) }}"
                    alt="RDSA">
            </a>

            <button
                class="navbar-toggler rdsa-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#rdsaNavigation"
                aria-controls="rdsaNavigation"
                aria-expanded="false"
                aria-label="Ouvrir la navigation">
                <i class="bi bi-list"></i>
            </button>

            <div class="collapse navbar-collapse" id="rdsaNavigation">

                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link active" href="#accueil">
                            Accueil
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#rdsa">
                            La RDSA
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#editions">
                            Éditions
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#medias">
                            Médias
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#participer">
                            Participer
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#contact">
                            Contact
                        </a>
                    </li>

                </ul>

                <a href="#participer" class="btn rdsa-navbar-button">
                    Je participe
                </a>

            </div>

        </div>
    </nav>

</header>


<main>

    {{-- HERO --}}
    @php($heroDesign = \App\Support\SiteDesign::resolve($edition))
    @php($heroSettings = $edition?->hero_settings ?? [])
    @php($heroSlides = collect([$edition?->hero_background_path ?: 'images/gallery/rdsa2/rdsa2-img-h46.png'])->merge($heroSettings['slides'] ?? [])->filter()->unique()->values())
    @if($heroDesign['template'])
    @include('hero.reference')
    @else
    <section id="accueil" class="rdsa-hero hero-model-{{ $heroDesign['layout'] }} hero-position-{{ $heroSettings['position'] ?? 'left' }} hero-font-{{ $heroSettings['font'] ?? 'original' }}" data-hero-mode="{{ $heroSettings['mode'] ?? 'zoom' }}" data-hero-interval="{{ $heroSettings['interval'] ?? 6 }}" style="--hero-accent:{{ $heroDesign['active'] ? $heroDesign['accent'] : ($heroSettings['accent'] ?? '#d6ad54') }};--hero-overlay:{{ ($heroSettings['overlay'] ?? 55) / 100 }}">

        {{-- IMAGE D'ARRIÈRE-PLAN AVEC EFFET PARALLAXE --}}
        <div class="rdsa-hero-background">
            @foreach($heroSlides as $heroSlide)<img class="hero-slide {{ $loop->first ? 'is-active' : '' }}" src="{{ asset($heroSlide) }}" alt="" aria-hidden="true" @if(!$loop->first) loading="lazy" @endif>@endforeach
        </div>

        @if($heroSlides->count() > 1)<div class="hero-slide-controls" aria-label="Diaporama de la bannière"><button type="button" data-hero-prev aria-label="Image précédente">‹</button><button type="button" data-hero-pause aria-label="Mettre en pause le diaporama">Ⅱ</button><button type="button" data-hero-next aria-label="Image suivante">›</button></div>@endif
        {{-- Voile sombre pour préserver la lisibilité --}}
        <div class="rdsa-hero-overlay"></div>

        {{-- Atmosphère --}}
        <div class="hero-glow hero-glow-one"></div>
        <div class="hero-glow hero-glow-two"></div>

        <div class="container">

            <div class="row align-items-center">

                {{-- CONTENU --}}
                <div class="col-lg-6">

                    <div class="rdsa-hero-content">

                        <div class="rdsa-edition-title">
                            @if($edition && preg_match('/^RDSA\s*' . $edition->number . '$/i', trim($edition->name)))
                            <span>RDSA</span><strong>{{ $edition->number }}</strong>
                            @else<span>{{ $edition?->name ?? 'RDSA' }}</span>@endif
                        </div>

                        <div class="rdsa-subtitle">
                            {{ $siteContent['hero_subtitle'] }}
                        </div>

                        <div class="rdsa-subtitle-gold">
                            {{ $siteContent['hero_subtitle_gold'] }}
                        </div>

                        <div class="rdsa-theme">

                            <span>Thème</span>

                            <p>
                                {{ $edition?->theme ?? 'Thème à confirmer' }}
                            </p>

                        </div>


                        <div class="rdsa-event-info">

                            <div class="rdsa-info-item">

                                <div class="rdsa-info-icon">
                                    <i class="bi bi-calendar3"></i>
                                </div>

                                <div>
                                    <strong>{{ $edition?->starts_at?->locale('fr')->translatedFormat('l d F Y') ?? 'Date à confirmer' }}</strong>

                                    <span>
                                        <i class="bi bi-clock"></i>
                                        {{ $edition?->starts_at?->format('H\hi') ?? 'Heure à confirmer' }}
                                    </span>
                                </div>

                            </div>


                            <div class="rdsa-info-item">

                                <div class="rdsa-info-icon">
                                    <i class="bi bi-geo-alt"></i>
                                </div>

                                <div>
                                    <span>
                                        {{ $edition?->venue ?? 'Lieu à confirmer' }}
                                    </span>

                                    <span>
                                        {{ $edition?->venue_details }}
                                    </span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- COMPOSITION PHOTOGRAPHIQUE --}}
                <div class="col-lg-6">

                    <div class="rdsa-hero-visual rdsa-hero-countdown-only">

                        {{-- Countdown --}}
                        <div class="rdsa-countdown" data-starts-at="{{ $edition?->starts_at?->toIso8601String() }}">

                            <div class="rdsa-countdown-heading">

                                <strong>J-</strong>
                                <strong id="daysup">00</strong>

                                <span>
                                    AVANT<br>
                                    {{ $edition?->name ?? 'LA PROCHAINE RDSA' }}
                                </span>

                            </div>

                            <div class="rdsa-countdown-grid">

                                <div class="time">
                                    <strong id="days">00</strong>
                                    <span>Jours</span>
                                </div>

                                <div class="time">
                                    <strong id="hours">00</strong>
                                    <span>Heures</span>
                                </div>

                                <div class="time">
                                    <strong id="minutes">00</strong>
                                    <span>Minutes</span>
                                </div>

                                <div class="time">
                                    <strong id="seconds">00</strong>
                                    <span>Secondes</span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        {{-- Transition vers la section suivante --}}
        <div class="hero-bottom-wave"></div>

    </section>
    @endif

    {{-- LA RDSA --}}
    <section id="rdsa" class="rdsa-about">

        <div class="container">
            <div class="row align-items-center g-5">

                {{-- Présentation --}}
                <div class="col-lg-4">

                    <div class="rdsa-about-intro">

                        <span class="section-kicker">
                            {{ $siteContent['about_kicker'] }}
                        </span>

                        <h2>
                            @php($heading = \App\Models\SiteContent::heading($siteContent['about_heading']))
                            {{ $heading[0] }} <span>{{ $heading[1] }}</span>
                        </h2>

                        <div class="section-line"></div>

                        <p>
                            {{ $siteContent['about_description'] }}
                        </p>

                        <a href="#participer" class="rdsa-outline-button">
                            {{ $siteContent['about_button'] }}
                        <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                </div>


                {{-- Les 4 axes --}}
                <div class="col-lg-8">

                    <div class="row g-0 rdsa-values">

                        {{-- 1 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-people"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_1_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_1_text'])) !!}
                                </p>

                            </article>

                        </div>


                        {{-- 2 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-fire"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_2_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_2_text'])) !!}
                                </p>

                            </article>

                        </div>


                        {{-- 3 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-book"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_3_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_3_text'])) !!}
                                </p>

                            </article>

                        </div>


                        {{-- 4 --}}
                        <div class="col-md-6 col-xl-3">

                            <article class="rdsa-value">

                                <div class="rdsa-value-icon">
                                    <i class="bi bi-heart"></i>
                                </div>

                                <h3>
                            {{ $siteContent['value_4_title'] }}
                        </h3>

                                <p>
                                    {!! nl2br(e($siteContent['value_4_text'])) !!}
                                </p>

                            </article>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>


<section id="editions" class="rdsa-editions"><div class="container"><div class="section-heading"><span class="section-kicker">{{ $siteContent['editions_kicker'] }}</span><h2>@php($heading = \App\Models\SiteContent::heading($siteContent['editions_heading']))
                            {{ $heading[0] }} <span>{{ $heading[1] }}</span></h2><div class="section-heading-line"></div></div><div class="row g-4">
@foreach(collect([$edition])->filter()->merge($previousEditions)->sortBy('number') as $past)
@php($cover = $past->hero_background_path ?: ([1 => 'images/gallery/rdsa1/rdsa1-flyer.jpg', 2 => 'images/gallery/rdsa2/rdsa2-flyer.jpg', 3 => 'images/gallery/rdsa3/rdsa3-flyer.jpeg'][$past->number] ?? 'images/gallery/rdsa2/rdsa2-img-h42.jpeg'))
<div class="col-md-6 col-lg-4"><article class="edition-card {{ $past->is_active ? 'edition-current' : '' }}"><div class="edition-image"><img src="{{ asset($cover) }}" alt="{{ $past->name }}" loading="lazy"><span class="edition-number">ÉDITION {{ $past->number }}</span>@if($past->is_active)<span class="edition-current-badge">ÉDITION ACTUELLE</span>@endif</div><div class="edition-content"><div><span class="edition-name">{{ $past->name }}</span><span class="edition-date">{{ $past->starts_at->locale('fr')->translatedFormat('d F Y') }}</span></div><a class="edition-link" href="{{ $past->is_active ? '#accueil' : '#medias' }}" @if(!$past->is_active) data-album-open="{{ $past->name }}" @endif>{{ $past->is_active ? 'DÉCOUVRIR' : 'VOIR PLUS' }} <i class="bi bi-arrow-right"></i></a></div></article></div>
@endforeach
</div></div></section>

@include('media.section')
@include('participations.form')
@include('contact.section')

</main>
    <footer class="rdsa-footer">

        <div class="container">

            <div class="row g-5">

                {{-- Logo --}}
                <div class="col-lg-4">

                    <a href="#accueil" class="footer-logo">

                        <img
                            src="{{ asset($siteContent['logo_path']) }}"
                            alt="RDSA">

                    </a>

                    <p class="footer-description">
                            {{ $siteContent['footer_description'] }}
                        </p>

                </div>


                {{-- Navigation --}}
                <div class="col-6 col-lg-2">

                    <h3>
                        NAVIGATION
                    </h3>

                    <ul class="footer-links">

                        <li>
                            <a href="#accueil">
                                Accueil
                            </a>
                        </li>

                        <li>
                            <a href="#rdsa">
                                La RDSA
                            </a>
                        </li>

                        <li>
                            <a href="#editions">
                                Éditions
                            </a>
                        </li>

                        <li>
                            <a href="#medias">
                                Médias
                            </a>
                        </li>

                        <li>
                            <a href="#contact">
                                Contact
                            </a>
                        </li>

                    </ul>

                </div>


                {{-- Éditions --}}
                <div class="col-6 col-lg-2">

                    <h3>
                        ÉDITIONS
                    </h3>

<ul class="footer-links">@foreach(collect([$edition])->filter()->merge($previousEditions)->sortBy('number') as $item)<li><a href="#editions">{{ $item->name }}</a></li>@endforeach</ul>

                </div>


                {{-- Participation --}}
                <div class="col-lg-4">

                    <div class="footer-participation">

                        <span>
                            {{ $edition?->name ?? 'RDSA' }}
                        </span>

                        <h3>
                            {{ $siteContent['footer_title'] }}
                        </h3>

                        <p>
                            {{ $siteContent['footer_invitation'] }}
                        </p>

                        <a href="#participer"
                            class="footer-button">

                            {{ $siteContent['footer_button'] }}

                            <i class="bi bi-arrow-right"></i>

                        </a>

                    </div>

                </div>

            </div>


            <div class="footer-bottom">

                <p>
                    © {{ date('Y') }} RDSA.
                    Tous droits réservés.
                </p>

                <p>
                    Dévéloppé par Jada Firmament.
                </p>

            </div>

        </div>

    </footer>
@include('tickets.lookup-dialog')
@include('messages.form')
@endsection
