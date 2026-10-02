@extends('layouts.app')

@section('title', 'RDSA - Réunion des Assoiffés de Prière & d’Adoration')

@section('content')

@php
$images = glob(public_path('images/gallery/rdsa2/*'));
@endphp
<header class="site-header">

    <nav class="navbar navbar-expand-lg rdsa-navbar">
        <div class="container">

            <a class="navbar-brand rdsa-logo" href="#accueil">
                <img
                    src="{{ asset('images/logo/logo.png') }}"
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
    <section id="accueil" class="rdsa-hero">

        {{-- Atmosphère --}}
        <div class="hero-glow hero-glow-one"></div>
        <div class="hero-glow hero-glow-two"></div>

        <div class="container">

            <div class="row align-items-center">

                {{-- CONTENU --}}
                <div class="col-lg-6">

                    <div class="rdsa-hero-content">

                        <div class="rdsa-edition-title">
                            <span>RDSA</span>
                            <strong>3</strong>
                        </div>

                        <div class="rdsa-subtitle">
                            RÉUNION DES ASSOIFFÉS
                        </div>

                        <div class="rdsa-subtitle-gold">
                            DE PRIÈRE & D’ADORATION
                        </div>

                        <div class="rdsa-theme">

                            <span>Thème</span>

                            <p>
                                QUE L’ÉTERNEL REMPLISSE
                                <br>
                                LES <strong>VASES VIDES</strong>
                            </p>

                        </div>


                        <div class="rdsa-event-info">

                            <div class="rdsa-info-item">

                                <div class="rdsa-info-icon">
                                    <i class="bi bi-calendar3"></i>
                                </div>

                                <div>
                                    <strong>SAMEDI 08 AOÛT 2026</strong>

                                    <span>
                                        <i class="bi bi-clock"></i>
                                        À PARTIR DE 08H00
                                    </span>
                                </div>

                            </div>


                            <div class="rdsa-info-item">

                                <div class="rdsa-info-icon">
                                    <i class="bi bi-geo-alt"></i>
                                </div>

                                <div>
                                    <span>
                                        TEMPLE DE LA JEUNESSE DE CAMP MILITAIRE
                                    </span>

                                    <span>
                                        PRÈS DU COLLÈGE SINGA YOPOUGON
                                    </span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- COMPOSITION PHOTOGRAPHIQUE --}}
                <div class="col-lg-6">

                    <div class="rdsa-hero-visual">

                        {{-- Grande photo paysage --}}
                        <div class="hero-photo hero-photo-wide">

                            <img
                                src="{{ asset('images/gallery/rdsa2/rdsa2-img-h42.jpeg') }}"
                                alt="Ambiance de la RDSA">

                        </div>


                        {{-- Photo portrait --}}
                        <div class="hero-photo hero-photo-portrait">

                            <img
                                src="{{ asset('images/gallery/rdsa3/rdsa3-flyer.jpeg') }}"
                                alt="Intervenant de la RDSA">

                        </div>


                        {{-- Lumière derrière les photos --}}
                        <div class="hero-photo-glow"></div>


                        {{-- Countdown --}}
                        <div class="rdsa-countdown">

                            <div class="rdsa-countdown-heading">

                                <strong>J-</strong>
                                <strong id="daysup">00</strong>

                                <span>
                                    AVANT<br>
                                    LA RDSA 3
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

    {{-- LA RDSA --}}
    <section id="rdsa" class="rdsa-about">

        <div class="container">
            <div class="row align-items-center g-5">

                {{-- Présentation --}}
                <div class="col-lg-4">

                    <div class="rdsa-about-intro">

                        <span class="section-kicker">
                            À PROPOS
                        </span>

                        <h2>
                            LA <span>RDSA</span>
                        </h2>

                        <div class="section-line"></div>

                        <p>
                            Une rencontre de prière, d’adoration et
                            d’enseignement pour rechercher la présence
                            de Dieu et manifester Sa gloire.
                        </p>

                        <a href="#participer" class="rdsa-outline-button">
                            EN SAVOIR PLUS
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
                                    UN SEUL BUT
                                </h3>

                                <p>
                                    RECHERCHER
                                    <br>
                                    LA PRÉSENCE
                                    <br>
                                    DE DIEU
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
                                    ADORATION
                                </h3>

                                <p>
                                    DES MOMENTS
                                    <br>
                                    PUISSANTS DE
                                    <br>
                                    LOUANGE
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
                                    ENSEIGNEMENTS
                                </h3>

                                <p>
                                    DES PAROLES
                                    <br>
                                    QUI TRANSFORMENT
                                    <br>
                                    LES VIES
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
                                    IMPACT
                                </h3>

                                <p>
                                    DES VIES
                                    <br>
                                    CHANGÉES POUR
                                    <br>
                                    SA GLOIRE
                                </p>

                            </article>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </section>

    {{-- NOS ÉDITIONS --}}

    <section id="editions" class="rdsa-editions">

        <div class="container">

            {{-- En-tête --}}
            <div class="section-heading">

                <span class="section-kicker">
                    NOTRE HISTOIRE
                </span>

                <h2>
                    NOS <span>ÉDITIONS</span>
                </h2>

                <div class="section-heading-line"></div>

            </div>


            {{-- CARTES DES ÉDITIONS --}}

            <div class="row g-4">

                {{-- RDSA 1 --}}
                <div class="col-md-6 col-lg-4">

                    <article class="edition-card">

                        <div class="edition-image">

                            <img
                                src="{{ asset('images/gallery/rdsa1/rdsa1-flyer.jpg') }}"
                                alt="RDSA édition 1">

                            <span class="edition-number">
                                ÉDITION 1
                            </span>

                        </div>


                        <div class="edition-content">

                            <div>
                                <span class="edition-name">
                                    RDSA 1
                                </span>

                                <span class="edition-date">
                                    28 MARS 2026
                                </span>
                            </div>

                            <a href="#medias" class="edition-link">
                                VOIR PLUS
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </article>

                </div>


                {{-- RDSA 2 --}}
                <div class="col-md-6 col-lg-4">

                    <article class="edition-card">

                        <div class="edition-image">

                            <img
                                src="{{ asset('images/gallery/rdsa2/rdsa2-flyer.jpg') }}"
                                alt="RDSA édition 2">

                            <span class="edition-number">
                                ÉDITION 2
                            </span>

                        </div>


                        <div class="edition-content">

                            <div>
                                <span class="edition-name">
                                    RDSA 2
                                </span>

                                <span class="edition-date">
                                    08 AOÛT 2026
                                </span>
                            </div>

                            <a href="#medias" class="edition-link">
                                VOIR PLUS
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </article>

                </div>


                {{-- RDSA 3 --}}
                <div class="col-md-6 col-lg-4">

                    <article class="edition-card edition-current">

                        <div class="edition-image">

                            <img
                                src="{{ asset('images/gallery/rdsa3/rdsa3-flyer.jpeg') }}"
                                alt="RDSA édition 3">

                            <span class="edition-number">
                                ÉDITION 3
                            </span>

                            <span class="edition-current-badge">
                                ÉDITION ACTUELLE
                            </span>

                        </div>


                        <div class="edition-content">

                            <div>
                                <span class="edition-name">
                                    RDSA 3
                                </span>

                                <span class="edition-date">
                                    AOÛT 2026
                                </span>
                            </div>

                            <a href="#accueil" class="edition-link">
                                DÉCOUVRIR
                                <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </article>

                </div>

            </div>

        </div>

    </section>

    {{-- MÉDIAS --}}

    <section id="medias" class="rdsa-media">

        <div class="container">

            <div class="row g-5 align-items-center">

                {{-- GALERIE PHOTOS --}}
                <div class="col-lg-7">

                    <div class="media-heading">

                        <span class="section-kicker">
                            REVIVEZ NOS MOMENTS
                        </span>

                        <h2>
                            GALERIE <span>PHOTOS</span>
                        </h2>

                    </div>


                    <div class="rdsa-gallery">

                        {{-- Grande image --}}
                        <div class="gallery-item gallery-main">

                            <div class="gallery-slideshow">

                                @foreach ($images as $index => $image)

                                <img
                                    src="{{ asset('images/gallery/rdsa2/' . basename($image)) }}"
                                    class="gallery-slide {{ $index === 0 ? 'active' : '' }}"
                                    alt="Moment de la RDSA">

                                @endforeach

                            </div>

                        </div>


                        {{-- Image portrait --}}
                        <div class="gallery-item gallery-portrait">

                            <img
                                src="{{ asset('images/gallery/rdsa2/rdsa2-img-h4.jpg') }}"
                                alt="Moment de la RDSA">

                        </div>


                        {{-- Petite image --}}
                        <div class="gallery-item gallery-small gallery-small-one">

                            <img
                                src="{{ asset('images/gallery/rdsa2/rdsa2-img-h5.jpg') }}"
                                alt="Moment de la RDSA">

                        </div>


                        {{-- Petite image --}}
                        <div class="gallery-item gallery-small gallery-small-two">

                            <img
                                src="{{ asset('images/gallery/rdsa2/rdsa2-img-h6.jpg') }}"
                                alt="Moment de la RDSA">

                        </div>


                        {{-- Bouton --}}
                        <a href="javascript:void(0)" class="gallery-more" id="openGallery">
                            VOIR TOUTES LES PHOTOS
                            <i class="bi bi-camera"></i>
                        </a>

                    </div>

                </div>


                {{-- VIDÉOS --}}
                <div class="col-lg-5">

                    <div class="media-heading">

                        <span class="section-kicker">
                            NOS SOUVENIRS EN IMAGES
                        </span>

                        <h2>
                            <span>VIDÉOS</span>
                        </h2>

                    </div>


                    <div class="rdsa-videos">

                        {{-- Vidéo 1 --}}
                        <article class="video-card">

                            <div class="video-thumbnail">

                                <img
                                    src="{{ asset('images/gallery/rdsa2/rdsa2-img-h7.jpg') }}"
                                    alt="Vidéo RDSA">

                                <button
                                    type="button"
                                    class="video-play"
                                    aria-label="Lire la vidéo">
                                    <i class="bi bi-play-fill"></i>
                                </button>

                            </div>

                        </article>


                        {{-- Vidéo 2 --}}
                        <article class="video-card">

                            <div class="video-thumbnail">

                                <img
                                    src="{{ asset('images/gallery/rdsa2/rdsa2-img-h8.jpg') }}"
                                    alt="Vidéo RDSA">

                                <button
                                    type="button"
                                    class="video-play"
                                    aria-label="Lire la vidéo">
                                    <i class="bi bi-play-fill"></i>
                                </button>

                            </div>

                        </article>


                        <a href="#" class="videos-more">
                            VOIR TOUTES LES VIDÉOS
                            <i class="bi bi-youtube"></i>
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>

    {{-- =========================================================
     PARTICIPER
========================================================= --}}

    <section id="participer" class="rdsa-participation">

        <div class="container">

            <div class="row align-items-center g-5">

                {{-- =====================================================
                 PRÉSENTATION
            ====================================================== --}}

                <div class="col-lg-5">

                    <div class="participation-intro">

                        <span class="section-kicker">
                            RDSA 3
                        </span>

                        <h2>
                            JE <span>PARTICIPE</span>
                        </h2>

                        <div class="section-line"></div>

                        <p>
                            Rejoins-nous pour cette nouvelle édition
                            de la Réunion des Assoiffés de Prière &
                            d’Adoration.
                        </p>


                        {{-- ÉVÉNEMENT --}}

                        <div class="participation-event-info">

                            <div class="event-info-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>

                            <div>
                                <span>PROCHAINE ÉDITION</span>

                                <strong id="participation_event_name">
                                    RDSA 3
                                </strong>

                                <p id="participation_event_date">
                                    Samedi 08 août 2026
                                </p>
                            </div>

                        </div>


                        {{-- PRIX --}}

                        <div class="participation-price">

                            <span>PARTICIPATION DE BASE</span>

                            <strong>
                                <span id="display_base_price">
                                    1 000
                                </span>

                                <small>FCFA / personne</small>
                            </strong>

                            <p>
                                Le montant est calculé automatiquement
                                selon le nombre de personnes.
                            </p>

                        </div>


                        {{-- ÉTAPES --}}

                        <div class="participation-steps-preview">

                            <div class="step-preview">
                                <span>01</span>
                                <p>Mes informations</p>
                            </div>

                            <div class="step-preview">
                                <span>02</span>
                                <p>Paiement Wave</p>
                            </div>

                            <div class="step-preview">
                                <span>03</span>
                                <p>Preuve de paiement</p>
                            </div>

                            <div class="step-preview">
                                <span>04</span>
                                <p>Vérification</p>
                            </div>

                            <div class="step-preview">
                                <span>05</span>
                                <p>Mon ticket</p>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                 FORMULAIRE
            ====================================================== --}}

                <div class="col-lg-7">

                    <div class="participation-card">


                        {{-- =================================================
                         PROGRESSION
                    ================================================== --}}

                        <div class="form-progress">

                            <div class="form-step active">
                                <span>1</span>
                                <small>Informations</small>
                            </div>

                            <div class="progress-line"></div>

                            <div class="form-step">
                                <span>2</span>
                                <small>Paiement</small>
                            </div>

                            <div class="progress-line"></div>

                            <div class="form-step">
                                <span>3</span>
                                <small>Preuve</small>
                            </div>

                            <div class="progress-line"></div>

                            <div class="form-step">
                                <span>4</span>
                                <small>Vérification</small>
                            </div>

                            <div class="progress-line"></div>

                            <div class="form-step">
                                <span>5</span>
                                <small>Ticket</small>
                            </div>

                        </div>


                        {{-- =================================================
                         ÉTAPE 1
                    ================================================== --}}

                        <div
                            class="participation-form-step active"
                            id="participation-step-1">

                            <div class="form-heading">

                                <span>ÉTAPE 01</span>

                                <h3>
                                    Tes informations
                                </h3>

                                <p>
                                    Quelques informations pour préparer
                                    ta participation.
                                </p>

                            </div>


                            <div class="row g-3">


                                {{-- NOM --}}

                                <div class="col-12 participation-field">

                                    <label for="participant_name">
                                        Nom complet
                                    </label>

                                    <input
                                        type="text"
                                        id="participant_name"
                                        name="participant_name"
                                        class="form-control rdsa-input"
                                        placeholder="Ex : Jean Daniel Ossan"
                                        autocomplete="name">

                                    <small class="field-error"></small>

                                </div>


                                {{-- TELEPHONE --}}

                                <div class="col-md-7 participation-field">

                                    <label for="participant_phone">
                                        Téléphone / WhatsApp
                                    </label>

                                    <input
                                        type="tel"
                                        id="participant_phone"
                                        name="participant_phone"
                                        class="form-control rdsa-input"
                                        placeholder="+225 07 XX XX XX XX"
                                        autocomplete="tel">

                                    <small class="field-error"></small>

                                </div>


                                {{-- NOMBRE --}}

                                <div class="col-md-5 participation-field">

                                    <label for="participant_quantity">
                                        Nombre de personnes
                                    </label>

                                    <div class="quantity-control">

                                        <button
                                            type="button"
                                            class="quantity-btn"
                                            id="quantity_minus"
                                            aria-label="Diminuer">
                                            <i class="bi bi-dash"></i>
                                        </button>

                                        <input
                                            type="number"
                                            id="participant_quantity"
                                            name="participant_quantity"
                                            value="1"
                                            min="1"
                                            readonly>

                                        <button
                                            type="button"
                                            class="quantity-btn"
                                            id="quantity_plus"
                                            aria-label="Augmenter">
                                            <i class="bi bi-plus"></i>
                                        </button>

                                    </div>

                                    <small class="field-error"></small>

                                </div>

                                {{-- HISTORIQUE DE PARTICIPATION --}}

                                <div class="participation-history">

                                    <label>
                                        Avez-vous déjà participé à une édition précédente de la RDSA ?
                                    </label>

                                    <div class="history-options">

                                        <button
                                            type="button"
                                            class="history-option"
                                            data-history="no">
                                            <i class="bi bi-circle"></i>

                                            <span>
                                                <strong>Non</strong>
                                                <small>C'est ma première participation</small>
                                            </span>
                                        </button>

                                        <button
                                            type="button"
                                            class="history-option"
                                            data-history="yes">
                                            <i class="bi bi-circle"></i>

                                            <span>
                                                <strong>Oui</strong>
                                                <small>J'ai déjà participé au RDSA</small>
                                            </span>
                                        </button>

                                    </div>


                                    <div
                                        id="previous-editions-container"
                                        class="previous-editions-container"
                                        hidden>

                                        <label>
                                            À quelle(s) édition(s) avez-vous participé ?
                                        </label>

                                        <div
                                            id="previous-editions-list"
                                            class="previous-editions-list"></div>

                                        <small class="input-help">
                                            Vous pouvez sélectionner plusieurs éditions.
                                        </small>

                                    </div>

                                </div>


                                {{-- CALCUL --}}

                                <div class="col-12">

                                    <div class="participation-calculation">

                                        <div class="calculation-row">

                                            <span>
                                                <span id="calculation_quantity">
                                                    1
                                                </span>
                                                personne
                                            </span>

                                            <strong>
                                                ×
                                                <span id="calculation_unit_price">
                                                    1 000
                                                </span>
                                                FCFA
                                            </strong>

                                        </div>


                                        <div class="calculation-divider"></div>


                                        <div class="calculation-total">

                                            <span>
                                                MONTANT DE BASE
                                            </span>

                                            <strong>
                                                <span id="calculation_base_amount">
                                                    1 000
                                                </span>

                                                <small>FCFA</small>
                                            </strong>

                                        </div>

                                    </div>

                                </div>


                                {{-- CONTRIBUTION --}}

                                <div
                                    class="col-12 participation-field"
                                    id="additional-contribution-container">

                                    <label for="participant_amount">
                                        Montant de participation
                                    </label>

                                    <div class="amount-input">

                                        <input
                                            type="number"
                                            id="participant_amount"
                                            name="participant_amount"
                                            class="form-control rdsa-input"
                                            min="1000"
                                            value="1000">

                                        <span>FCFA</span>

                                    </div>

                                    <small class="input-help">
                                        Tu peux conserver le montant calculé
                                        ou ajouter une contribution supplémentaire.
                                    </small>

                                    <small class="field-error"></small>

                                </div>


                                {{-- TOTAL FINAL --}}

                                <div class="col-12">

                                    <div class="participation-final-amount">

                                        <div>
                                            <span>
                                                TOTAL À PAYER
                                            </span>

                                            <p>
                                                Montant minimum calculé
                                                automatiquement.
                                            </p>
                                        </div>

                                        <strong>
                                            <span id="final_amount">
                                                1 000
                                            </span>

                                            <small>FCFA</small>
                                        </strong>

                                    </div>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="participation-next"
                                onclick="nextParticipationStep(2)">
                                CONTINUER

                                <i class="bi bi-arrow-right"></i>
                            </button>

                        </div>


                        {{-- =================================================
                         ÉTAPE 2
                    ================================================== --}}

                        <div
                            class="participation-form-step"
                            id="participation-step-2">

                            <div class="form-heading">

                                <span>ÉTAPE 02</span>

                                <h3>
                                    Effectue ton paiement
                                </h3>

                                <p>
                                    Choisis un numéro Wave actif puis
                                    envoie exactement le montant indiqué.
                                </p>

                            </div>


                            {{-- MONTANT --}}

                            <div class="wave-payment-amount">

                                <span>
                                    MONTANT À ENVOYER
                                </span>

                                <strong>
                                    <span id="wave_amount">
                                        1 000
                                    </span>

                                    <small>FCFA</small>
                                </strong>

                            </div>


                            {{-- NUMÉROS WAVE --}}

                            <div class="wave-selection-heading">
                                <span>
                                    CHOISIS UN NUMÉRO WAVE
                                </span>

                                <small>
                                    Sélectionne le numéro vers lequel
                                    tu souhaites effectuer le dépôt.
                                </small>
                            </div>


                            <div
                                class="wave-numbers"
                                id="wave_numbers">

                                {{-- Les numéros seront injectés
                                 par JavaScript depuis la configuration. --}}

                            </div>


                            <div
                                class="wave-selection-error"
                                id="wave_selection_error">
                                <i class="bi bi-exclamation-circle"></i>

                                Veuillez sélectionner un numéro Wave.
                            </div>


                            {{-- INSTRUCTION --}}

                            <div class="payment-instruction">

                                <i class="bi bi-phone"></i>

                                <div>

                                    <strong>
                                        Comment payer ?
                                    </strong>

                                    <p>
                                        Ouvre Wave, envoie le montant affiché
                                        vers le numéro choisi, puis conserve
                                        la référence de transaction.
                                    </p>

                                </div>

                            </div>


                            <div class="form-actions">

                                <button
                                    type="button"
                                    class="participation-back"
                                    onclick="nextParticipationStep(1)">
                                    <i class="bi bi-arrow-left"></i>
                                    RETOUR
                                </button>

                                <button
                                    type="button"
                                    class="participation-next"
                                    onclick="nextParticipationStep(3)">
                                    J'AI PAYÉ
                                    <i class="bi bi-arrow-right"></i>
                                </button>

                            </div>

                        </div>


                        {{-- =================================================
                         ÉTAPE 3
                    ================================================== --}}

                        <div
                            class="participation-form-step"
                            id="participation-step-3">

                            <div class="form-heading">

                                <span>ÉTAPE 03</span>

                                <h3>
                                    Confirme ton paiement
                                </h3>

                                <p>
                                    Renseigne uniquement les informations
                                    visibles sur ta transaction Wave.
                                </p>

                            </div>


                            <div class="row g-3">


                                {{-- REFERENCE --}}

                                <div class="col-12 participation-field">

                                    <label for="transaction_reference">
                                        Référence de transaction
                                    </label>

                                    <input
                                        type="text"
                                        id="transaction_reference"
                                        class="form-control rdsa-input"
                                        placeholder="Ex : 123456789"
                                        autocomplete="off">

                                    <small class="field-error"></small>

                                </div>


                                {{-- MONTANT --}}

                                <div class="col-12 participation-field">

                                    <label for="payment_amount">
                                        Montant envoyé
                                    </label>

                                    <div class="amount-input">

                                        <input
                                            type="number"
                                            id="payment_amount"
                                            class="form-control rdsa-input"
                                            min="1000">

                                        <span>FCFA</span>

                                    </div>

                                    <small class="input-help">
                                        Le montant doit être au moins égal
                                        au montant demandé.
                                    </small>

                                    <small class="field-error"></small>

                                </div>


                                {{-- DATE AUTOMATIQUE --}}

                                <div class="col-12">

                                    <div class="automatic-date-card">

                                        <div class="automatic-date-icon">
                                            <i class="bi bi-clock-history"></i>
                                        </div>

                                        <div>

                                            <span>
                                                DATE DE LA DEMANDE
                                            </span>

                                            <strong id="submission_datetime">
                                                —
                                            </strong>

                                            <p>
                                                Cette date est enregistrée
                                                automatiquement.
                                            </p>

                                        </div>

                                    </div>

                                </div>


                                {{-- PREUVE --}}

                                <div class="col-12 participation-field">

                                    <label for="payment_proof">
                                        Preuve de paiement
                                    </label>

                                    <div class="proof-upload">

                                        <input
                                            type="file"
                                            id="payment_proof"
                                            class="form-control rdsa-input"
                                            accept="image/jpeg,image/png,image/webp">

                                        <div class="proof-upload-overlay">

                                            <i class="bi bi-cloud-arrow-up"></i>

                                            <strong>
                                                Ajouter la capture Wave
                                            </strong>

                                            <span>
                                                JPG, PNG ou WebP · 5 Mo maximum
                                            </span>

                                        </div>

                                    </div>

                                    <small class="field-error"></small>

                                </div>


                            </div>


                            <div class="form-actions">

                                <button
                                    type="button"
                                    class="participation-back"
                                    onclick="nextParticipationStep(2)">
                                    <i class="bi bi-arrow-left"></i>
                                    RETOUR
                                </button>

                                <button
                                    type="button"
                                    class="participation-next"
                                    onclick="nextParticipationStep(4)">
                                    VÉRIFIER
                                    <i class="bi bi-arrow-right"></i>
                                </button>

                            </div>

                        </div>


                        {{-- =================================================
                         ÉTAPE 4 — VÉRIFICATION
                    ================================================== --}}

                        <div
                            class="participation-form-step"
                            id="participation-step-4">

                            <div class="verification-header">

                                <div class="verification-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>

                                <div>

                                    <span>ÉTAPE 04</span>

                                    <h3>
                                        Vérifie avant d'envoyer
                                    </h3>

                                    <p>
                                        Tout est prêt. Vérifie simplement
                                        les informations ci-dessous.
                                    </p>

                                </div>

                            </div>


                            <div class="verification-card">


                                {{-- PARTICIPANT --}}

                                <div class="verification-block">

                                    <div class="verification-block-heading">

                                        <div>
                                            <i class="bi bi-person"></i>

                                            <span>
                                                PARTICIPANT
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            onclick="nextParticipationStep(1)">
                                            MODIFIER
                                        </button>

                                    </div>


                                    <div class="verification-grid">

                                        <div class="verification-value">
                                            <small>Nom complet</small>
                                            <strong id="review_name">—</strong>
                                        </div>

                                        <div class="verification-value">
                                            <small>Téléphone</small>
                                            <strong id="review_phone">—</strong>
                                        </div>

                                        <div class="verification-value">
                                            <small>Participants</small>
                                            <strong id="review_quantity">—</strong>
                                        </div>

                                        <div class="verification-value">
                                            <small>Montant</small>
                                            <strong
                                                class="verification-price"
                                                id="review_amount">
                                                —
                                            </strong>
                                        </div>

                                    </div>

                                </div>


                                {{-- PAIEMENT --}}

                                <div class="verification-block">

                                    <div class="verification-block-heading">

                                        <div>
                                            <i class="bi bi-wallet2"></i>

                                            <span>
                                                PAIEMENT
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            onclick="nextParticipationStep(2)">
                                            MODIFIER
                                        </button>

                                    </div>


                                    <div class="verification-payment">

                                        <div class="wave-badge">
                                            <i class="bi bi-phone"></i>
                                            WAVE
                                        </div>

                                        <div>

                                            <small>
                                                Numéro sélectionné
                                            </small>

                                            <strong id="review_wave">
                                                —
                                            </strong>

                                        </div>

                                    </div>


                                    <div class="verification-grid">

                                        <div class="verification-value">
                                            <small>Référence</small>
                                            <strong id="review_reference">
                                                —
                                            </strong>
                                        </div>

                                        <div class="verification-value">
                                            <small>Montant envoyé</small>
                                            <strong
                                                class="verification-price"
                                                id="review_payment_amount">
                                                —
                                            </strong>
                                        </div>

                                    </div>

                                </div>


                                {{-- PREUVE --}}

                                <div class="verification-block">

                                    <div class="verification-block-heading">

                                        <div>
                                            <i class="bi bi-image"></i>

                                            <span>
                                                PREUVE DE PAIEMENT
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            onclick="nextParticipationStep(3)">
                                            MODIFIER
                                        </button>

                                    </div>


                                    <div class="proof-preview">

                                        <div class="proof-preview-image">

                                            <img
                                                id="review_proof_image"
                                                src=""
                                                alt="Aperçu de la preuve de paiement">

                                            <div
                                                class="proof-preview-placeholder"
                                                id="review_proof_placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>

                                        </div>


                                        <div class="proof-preview-info">

                                            <span>
                                                FICHIER
                                            </span>

                                            <strong id="review_proof">
                                                —
                                            </strong>

                                            <small>
                                                Image prête à être transmise
                                            </small>

                                        </div>

                                    </div>

                                </div>


                                {{-- TOTAL --}}

                                <div class="verification-total">

                                    <div>
                                        <span>
                                            TOTAL DE LA PARTICIPATION
                                        </span>

                                        <small>
                                            Montant qui sera enregistré
                                        </small>
                                    </div>

                                    <strong>
                                        <span id="review_total">
                                            1 000
                                        </span>

                                        <small>FCFA</small>
                                    </strong>

                                </div>

                            </div>


                            <div class="verification-notice">

                                <i class="bi bi-info-circle"></i>

                                <p>
                                    En confirmant, ta demande sera enregistrée
                                    et transmise pour vérification du paiement.
                                </p>

                            </div>


                            <div class="form-actions">

                                <button
                                    type="button"
                                    class="participation-back"
                                    onclick="nextParticipationStep(3)">
                                    <i class="bi bi-arrow-left"></i>
                                    MODIFIER
                                </button>

                                <button
                                    type="button"
                                    class="participation-next"
                                    onclick="confirmParticipation()">
                                    CONFIRMER
                                    <i class="bi bi-check2"></i>
                                </button>

                            </div>

                        </div>


                        {{-- =================================================
                         ÉTAPE 5 — TICKET
                    ================================================== --}}

                        <div
                            class="participation-form-step"
                            id="participation-step-5">

                            <div class="ticket-success">

                                <div class="ticket-icon">
                                    <i class="bi bi-ticket-perforated"></i>
                                </div>

                                <span>
                                    PARTICIPATION ENREGISTRÉE
                                </span>

                                <h3>
                                    Ta demande est enregistrée
                                </h3>

                                <p>
                                    Ton paiement doit maintenant être vérifié
                                    par notre équipe.
                                </p>


                                <div class="ticket-preview">

                                    <div class="ticket-brand">
                                        RDSA 3
                                    </div>

                                    <div class="ticket-reference">
                                        —
                                    </div>

                                    <div class="ticket-status">

                                        <i class="bi bi-hourglass-split"></i>

                                        EN ATTENTE DE VÉRIFICATION

                                    </div>


                                    <div class="ticket-details">

                                        <div>
                                            <span>PARTICIPANT</span>
                                            <strong id="ticket_name">
                                                —
                                            </strong>
                                        </div>

                                        <div>
                                            <span>MONTANT</span>
                                            <strong id="ticket_amount">
                                                —
                                            </strong>
                                        </div>

                                    </div>


                                    <div class="ticket-qr">

                                        <i class="bi bi-qr-code"></i>

                                    </div>

                                </div>


                                <small>
                                    Conserve précieusement ta référence.
                                    Elle pourra être utilisée pour retrouver
                                    ta participation.
                                </small>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    {{-- CONTACT --}}

    <section id="contact" class="rdsa-contact">

        <div class="container">

            <div class="row g-5 align-items-center">

                {{-- Informations --}}
                <div class="col-lg-5">

                    <div class="contact-intro">

                        <span class="section-kicker">
                            RESTONS EN CONTACT
                        </span>

                        <h2>
                            NOUS <span>CONTACTER</span>
                        </h2>

                        <div class="section-line"></div>

                        <p>
                            Une question concernant la RDSA, la participation
                            ou le déroulement de l'événement ?
                            Notre équipe est disponible pour t'accompagner.
                        </p>

                    </div>


                    <div class="contact-info-list">

                        <a href="tel:+2250000000000"
                            class="contact-info-item">

                            <div class="contact-icon">
                                <i class="bi bi-telephone"></i>
                            </div>

                            <div>
                                <span>TÉLÉPHONE</span>
                                <strong>+225 XX XX XX XX XX</strong>
                            </div>

                        </a>


                        <a href="https://wa.me/2250000000000"
                            class="contact-info-item"
                            target="_blank"
                            rel="noopener">

                            <div class="contact-icon">
                                <i class="bi bi-whatsapp"></i>
                            </div>

                            <div>
                                <span>WHATSAPP</span>
                                <strong>Nous écrire sur WhatsApp</strong>
                            </div>

                        </a>


                        <div class="contact-info-item">

                            <div class="contact-icon">
                                <i class="bi bi-geo-alt"></i>
                            </div>

                            <div>
                                <span>LIEU</span>
                                <strong>
                                    Temple de la Jeunesse<br>
                                    de Camp Militaire
                                </strong>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- Carte / localisation --}}
                <div class="col-lg-7">

                    <div class="contact-map">

                        <div class="map-decoration map-decoration-one"></div>
                        <div class="map-decoration map-decoration-two"></div>

                        <div class="map-content">

                            <div class="map-pin">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>

                            <span>
                                NOUS SOMMES ICI
                            </span>

                            <h3>
                                Camp Militaire
                            </h3>

                            <p>
                                Près du Collège Singa<br>
                                Yopougon, Abidjan
                            </p>

                            <a href="#"
                                class="map-button">

                                VOIR L'ITINÉRAIRE

                                <i class="bi bi-arrow-up-right"></i>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- FOOTER --}}

    <footer class="rdsa-footer">

        <div class="container">

            <div class="row g-5">

                {{-- Logo --}}
                <div class="col-lg-4">

                    <a href="#accueil" class="footer-logo">

                        <img
                            src="{{ asset('images/logo/logo.png') }}"
                            alt="RDSA">

                    </a>

                    <p class="footer-description">
                        Réunion des Assoiffés de Prière & d’Adoration.
                        Une génération qui a soif de Dieu.
                    </p>

                    <div class="footer-socials">

                        <a href="#" aria-label="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>

                        <a href="#" aria-label="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>

                        <a href="#" aria-label="YouTube">
                            <i class="bi bi-youtube"></i>
                        </a>

                        <a href="#" aria-label="TikTok">
                            <i class="bi bi-tiktok"></i>
                        </a>

                        <a href="#" aria-label="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>

                    </div>

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

                    <ul class="footer-links">

                        <li>
                            <a href="#editions">
                                RDSA 1
                            </a>
                        </li>

                        <li>
                            <a href="#editions">
                                RDSA 2
                            </a>
                        </li>

                        <li>
                            <a href="#accueil">
                                RDSA 3
                            </a>
                        </li>

                    </ul>

                </div>


                {{-- Participation --}}
                <div class="col-lg-4">

                    <div class="footer-participation">

                        <span>
                            RDSA 3
                        </span>

                        <h3>
                            PRÊT À NOUS REJOINDRE ?
                        </h3>

                        <p>
                            Réserve ta participation et prépare-toi
                            à vivre un moment particulier dans
                            la présence de Dieu.
                        </p>

                        <a href="#participer"
                            class="footer-button">

                            JE PARTICIPE

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
                    Fait avec foi & passion.
                </p>

            </div>

        </div>

    </footer>

</main>


{{-- MODAL GALERIE --}}

<div id="galleryModal" class="gallery-modal">

    <div class="gallery-box">

        {{-- Fermer --}}
        <button type="button" id="closeGallery" class="gallery-close">
            <i class="bi bi-x-lg"></i>
        </button>

        {{-- Image --}}
        <div class="gallery-image-container">

            @foreach ($images as $image)

            <img
                src="{{ asset('images/gallery/rdsa2/' . basename($image)) }}"
                class="gallery-image"
                alt="Photo RDSA">

            @endforeach

        </div>

        {{-- Flèche gauche --}}
        <button type="button" id="prevPhoto" class="gallery-arrow gallery-prev">
            <i class="bi bi-chevron-left"></i>
        </button>

        {{-- Flèche droite --}}
        <button type="button" id="nextPhoto" class="gallery-arrow gallery-next">
            <i class="bi bi-chevron-right"></i>
        </button>

        {{-- Compteur --}}
        <div class="gallery-counter">
            <span id="photoNumber">1</span>
            /
            {{ count($images) }}
        </div>

    </div>

</div>

@endsection