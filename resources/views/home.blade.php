<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>RDSA</title>
</head>

<body>

    @extends('layouts.app')

    @section('title', 'RDSA - Réunion des Assoiffés de Prière & d’Adoration')

    @section('content')

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
                                    src="{{ asset('images/hero/rdsa3-room.jpg') }}"
                                    alt="Ambiance de la RDSA">

                            </div>


                            {{-- Photo portrait --}}
                            <div class="hero-photo hero-photo-portrait">

                                <img
                                    src="{{ asset('images/hero/rdsa3-speaker.png') }}"
                                    alt="Intervenant de la RDSA">

                            </div>


                            {{-- Lumière derrière les photos --}}
                            <div class="hero-photo-glow"></div>


                            {{-- Countdown --}}
                            <div class="rdsa-countdown">

                                <div class="rdsa-countdown-heading">

                                    <strong>J-242</strong>

                                    <span>
                                        AVANT<br>
                                        LA RDSA 3
                                    </span>

                                </div>

                                <div class="rdsa-countdown-grid">

                                    <div>
                                        <strong>242</strong>
                                        <span>Jours</span>
                                    </div>

                                    <div>
                                        <strong>10</strong>
                                        <span>Heures</span>
                                    </div>

                                    <div>
                                        <strong>45</strong>
                                        <span>Minutes</span>
                                    </div>

                                    <div>
                                        <strong>32</strong>
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

    </main>

    @endsection

</body>

</html>