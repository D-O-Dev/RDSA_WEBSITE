@extends('layouts.app')

@section('title', 'Confirmation de participation — RDSA')

@section('content')

<style>
    /* =========================================
       CONFIRMATION RDSA
    ========================================= */

    .rdsa-confirmation {
        --confirmation-blue: #102d69;
        --confirmation-blue-light: #2457a7;
        --confirmation-gold: #d8b45a;
        --confirmation-text: #17233b;
        --confirmation-muted: #68758b;

        position: relative;
        min-height: 85vh;
        padding: 75px 20px 90px;
        overflow: hidden;
        background:
            radial-gradient(
                circle at 10% 10%,
                rgba(48, 94, 173, 0.12),
                transparent 32%
            ),
            radial-gradient(
                circle at 90% 80%,
                rgba(216, 180, 90, 0.12),
                transparent 30%
            ),
            #f5f7fc;
        font-family: inherit;
        color: var(--confirmation-text);
    }

    .rdsa-confirmation *,
    .rdsa-confirmation *::before,
    .rdsa-confirmation *::after {
        box-sizing: border-box;
    }

    .confirmation-container {
        position: relative;
        z-index: 1;
        max-width: 900px;
        margin: 0 auto;
    }

    /* En-tête de félicitations */

    .confirmation-hero {
        position: relative;
        padding: 45px 30px 40px;
        overflow: hidden;
        border-radius: 28px;
        text-align: center;
        color: #fff;
        background: linear-gradient(
            135deg,
            #0b1e49 0%,
            #173c80 55%,
            #2857a1 100%
        );
        box-shadow: 0 22px 55px rgba(16, 45, 105, 0.18);
    }

    .confirmation-hero::before,
    .confirmation-hero::after {
        content: "";
        position: absolute;
        width: 250px;
        height: 250px;
        border: 1px solid rgba(255, 255, 255, 0.10);
        border-radius: 50%;
        pointer-events: none;
    }

    .confirmation-hero::before {
        top: -150px;
        right: -70px;
    }

    .confirmation-hero::after {
        bottom: -180px;
        left: -70px;
        width: 310px;
        height: 310px;
    }

    .confirmation-hero-content {
        position: relative;
        z-index: 1;
    }

    .confirmation-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88px;
        height: 88px;
        margin: 0 auto 25px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 50%;
        color: #fff;
        background: rgba(255, 255, 255, 0.10);
        box-shadow: 0 0 0 10px rgba(255, 255, 255, 0.035);
        animation: confirmation-appear 0.65s ease both;
    }

    .confirmation-icon svg {
        width: 42px;
        height: 42px;
        stroke: var(--confirmation-gold);
    }

    .confirmation-eyebrow {
        margin: 0 0 12px;
        color: var(--confirmation-gold);
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 3px;
        text-transform: uppercase;
    }

    .confirmation-title {
        max-width: 650px;
        margin: 0 auto 16px;
        color: #fff;
        font-size: clamp(2rem, 5vw, 3.2rem);
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -1.2px;
    }

    .confirmation-description {
        max-width: 570px;
        margin: 0 auto;
        color: rgba(255, 255, 255, 0.83);
        font-size: 1rem;
        line-height: 1.8;
    }

    .confirmation-highlight {
        color: var(--confirmation-gold);
        font-weight: 700;
    }

    /* Statut */

    .confirmation-status {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin: 28px 0;
        padding: 22px 24px;
        border: 1px solid #ead9a9;
        border-radius: 18px;
        background: #fffdf6;
    }

    .confirmation-status-icon {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 13px;
        color: #8b6814;
        background: #f8edc9;
    }

    .confirmation-status-icon svg {
        width: 23px;
        height: 23px;
    }

    .confirmation-status h2 {
        margin: 1px 0 6px;
        color: #70520d;
        font-size: 1rem;
        font-weight: 800;
    }

    .confirmation-status p {
        margin: 0;
        color: #756b50;
        font-size: 0.91rem;
        line-height: 1.7;
    }

    /* Récapitulatif */

    .confirmation-summary {
        padding: 32px;
        border: 1px solid #e7ebf3;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 12px 40px rgba(24, 44, 82, 0.055);
    }

    .confirmation-section-heading {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 27px;
    }

    .confirmation-section-icon {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 15px;
        color: var(--confirmation-blue);
        background: #edf3ff;
    }

    .confirmation-section-icon svg {
        width: 25px;
        height: 25px;
    }

    .confirmation-section-heading h2 {
        margin: 0 0 5px;
        color: var(--confirmation-text);
        font-size: 1.25rem;
        font-weight: 800;
    }

    .confirmation-section-heading p {
        margin: 0;
        color: var(--confirmation-muted);
        font-size: 0.88rem;
    }

    .confirmation-reference {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 25px;
        padding: 18px 20px;
        border: 1px dashed #c7d4eb;
        border-radius: 15px;
        background: #f6f9ff;
    }

    .confirmation-reference-label {
        display: block;
        margin-bottom: 6px;
        color: var(--confirmation-muted);
        font-size: 0.78rem;
    }

    .confirmation-reference-value {
        color: var(--confirmation-blue);
        font-size: 1.05rem;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .confirmation-reference-badge {
        flex-shrink: 0;
        padding: 7px 10px;
        border-radius: 8px;
        color: #31588f;
        background: #e4edff;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .confirmation-details {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .confirmation-detail {
        min-width: 0;
        padding: 17px;
        border: 1px solid #edf0f6;
        border-radius: 14px;
        background: #fff;
    }

    .confirmation-detail-label {
        display: block;
        margin-bottom: 8px;
        color: var(--confirmation-muted);
        font-size: 0.82rem;
    }

    .confirmation-detail-value {
        display: block;
        color: var(--confirmation-text);
        font-size: 0.97rem;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .confirmation-amounts {
        margin-top: 25px;
        padding-top: 22px;
        border-top: 1px solid #edf0f6;
    }

    .confirmation-amount-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 15px;
        color: var(--confirmation-muted);
        font-size: 0.92rem;
    }

    .confirmation-amount-row span:last-child {
        color: var(--confirmation-text);
        font-weight: 700;
        text-align: right;
    }

    .confirmation-amount-total {
        margin-top: 20px;
        padding: 20px;
        border-radius: 15px;
        background: linear-gradient(135deg, #edf3ff, #f5f7ff);
    }

    .confirmation-amount-total .confirmation-amount-row {
        align-items: center;
        margin: 0;
        color: var(--confirmation-blue);
        font-size: 1rem;
        font-weight: 800;
    }

    .confirmation-amount-total .confirmation-amount-row span:last-child {
        color: var(--confirmation-blue);
        font-size: clamp(1.15rem, 3vw, 1.5rem);
        font-weight: 900;
    }

    /* Étapes suivantes */

    .confirmation-next {
        margin-top: 28px;
        padding: 27px 30px;
        border-radius: 20px;
        background: #fff;
        border: 1px solid #e7ebf3;
    }

    .confirmation-next h2 {
        margin: 0 0 18px;
        color: var(--confirmation-text);
        font-size: 1.1rem;
        font-weight: 800;
    }

    .confirmation-next-step {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        margin-top: 15px;
    }

    .confirmation-next-number {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 10px;
        color: var(--confirmation-blue);
        background: #edf3ff;
        font-size: 0.85rem;
        font-weight: 800;
    }

    .confirmation-next-step p {
        margin: 3px 0 0;
        color: var(--confirmation-muted);
        font-size: 0.91rem;
        line-height: 1.7;
    }

    .confirmation-next-step strong {
        color: var(--confirmation-text);
    }

    /* Actions */

    .confirmation-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 13px;
        margin-top: 30px;
    }

    .confirmation-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 52px;
        padding: 14px 24px;
        border: 1px solid transparent;
        border-radius: 13px;
        text-decoration: none;
        font-size: 0.93rem;
        font-weight: 800;
        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease,
            background 0.2s ease;
    }

    .confirmation-button:hover {
        transform: translateY(-2px);
    }

    .confirmation-button-primary {
        color: #fff;
        background: linear-gradient(135deg, #173c80, #2857a1);
        box-shadow: 0 9px 22px rgba(23, 60, 128, 0.19);
    }

    .confirmation-button-primary:hover {
        color: #fff;
        box-shadow: 0 12px 27px rgba(23, 60, 128, 0.27);
    }

    .confirmation-button-secondary {
        color: var(--confirmation-blue);
        border-color: #dce4f2;
        background: #fff;
    }

    .confirmation-button-secondary:hover {
        color: var(--confirmation-blue);
        background: #f6f9ff;
    }

    .confirmation-button svg {
        width: 19px;
        height: 19px;
    }

    .confirmation-footer {
        margin-top: 35px;
        color: var(--confirmation-muted);
        font-size: 0.82rem;
        text-align: center;
        line-height: 1.8;
    }

    .confirmation-footer strong {
        color: var(--confirmation-blue);
    }

    @keyframes confirmation-appear {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.92);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Adaptation mobile */

    @media (max-width: 600px) {
        .rdsa-confirmation {
            padding: 35px 14px 55px;
        }

        .confirmation-hero {
            padding: 36px 20px 32px;
            border-radius: 22px;
        }

        .confirmation-icon {
            width: 75px;
            height: 75px;
            margin-bottom: 22px;
        }

        .confirmation-title {
            letter-spacing: -0.6px;
        }

        .confirmation-description {
            font-size: 0.92rem;
        }

        .confirmation-status {
            padding: 18px;
        }

        .confirmation-summary {
            padding: 22px 17px;
            border-radius: 20px;
        }

        .confirmation-reference {
            align-items: flex-start;
            flex-direction: column;
            padding: 16px;
        }

        .confirmation-details {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .confirmation-detail {
            padding: 15px;
        }

        .confirmation-next {
            padding: 22px 18px;
        }

        .confirmation-actions {
            flex-direction: column;
        }

        .confirmation-button {
            width: 100%;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .rdsa-confirmation *,
        .rdsa-confirmation *::before,
        .rdsa-confirmation *::after {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<main class="rdsa-confirmation">
    <div class="confirmation-container">

        {{-- FÉLICITATIONS --}}
        <section class="confirmation-hero">
            <div class="confirmation-hero-content">

                <div class="confirmation-icon" aria-hidden="true">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>

                <p class="confirmation-eyebrow">
                    RDSA · Confirmation
                </p>

                <h1 class="confirmation-title">
                    Félicitations,
                    {{ explode(' ', trim($participation->participant->full_name))[0] }} !
                </h1>

                <p class="confirmation-description">
                    Ta demande de participation à
                    <span class="confirmation-highlight">
                        {{ $participation->edition->name }}
                    </span>
                    a bien été enregistrée.

                    Merci de prendre part à cette rencontre de prière
                    et d’adoration.
                </p>

            </div>
        </section>

        {{-- STATUT DU PAIEMENT --}}
        <section class="confirmation-status" aria-live="polite">

            <div class="confirmation-status-icon" aria-hidden="true">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 7v5l3 2"></path>
                </svg>
            </div>

            <div>
                <h2>Paiement en attente de vérification</h2>

                <p>
                    Ta déclaration de paiement a été enregistrée.
                    L’équipe RDSA doit maintenant vérifier les informations
                    et la preuve transmises avant de confirmer définitivement
                    ta participation.
                </p>
            </div>

        </section>

        {{-- RÉCAPITULATIF --}}
        <section class="confirmation-summary">

            <div class="confirmation-section-heading">

                <div class="confirmation-section-icon" aria-hidden="true">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="4" y="3" width="16" height="18" rx="2"></rect>
                        <path d="M8 8h8M8 12h8M8 16h4"></path>
                    </svg>
                </div>

                <div>
                    <h2>Le récapitulatif de ta participation</h2>
                    <p>Conserve ta référence pour retrouver ta demande.</p>
                </div>

            </div>

            <div class="confirmation-reference">

                <div>
                    <span class="confirmation-reference-label">
                        Référence de participation
                    </span>

                    <span class="confirmation-reference-value">
                        {{ $participation->reference }}
                    </span>
                </div>

                <span class="confirmation-reference-badge">
                    ENREGISTRÉE
                </span>

            </div>

            <div class="confirmation-details">

                <div class="confirmation-detail">
                    <span class="confirmation-detail-label">
                        Participant
                    </span>

                    <span class="confirmation-detail-value">
                        {{ $participation->participant->full_name }}
                    </span>
                </div>

                <div class="confirmation-detail">
                    <span class="confirmation-detail-label">
                        Édition
                    </span>

                    <span class="confirmation-detail-value">
                        {{ $participation->edition->name }}
                    </span>
                </div>

                <div class="confirmation-detail">
                    <span class="confirmation-detail-label">
                        Nombre de places
                    </span>

                    <span class="confirmation-detail-value">
                        {{ $participation->quantity }}
                        {{ $participation->quantity > 1 ? 'places' : 'place' }}
                    </span>
                </div>

                <div class="confirmation-detail">
                    <span class="confirmation-detail-label">
                        Statut de la demande
                    </span>

                    <span class="confirmation-detail-value">
                        {{ $participation->status === 'pending'
                            ? 'En attente de confirmation'
                            : ucfirst($participation->status) }}
                    </span>
                </div>

            </div>

            <div class="confirmation-amounts">

                <div class="confirmation-amount-row">
                    <span>Prix unitaire</span>

                    <span>
                        {{ number_format($participation->unit_price, 0, ',', ' ') }}
                        FCFA
                    </span>
                </div>

                <div class="confirmation-amount-row">
                    <span>
                        Participation
                        ({{ $participation->quantity }} place(s))
                    </span>

                    <span>
                        {{ number_format($participation->base_amount, 0, ',', ' ') }}
                        FCFA
                    </span>
                </div>

                @if ($participation->extra_contribution > 0)
                    <div class="confirmation-amount-row">
                        <span>Contribution supplémentaire</span>

                        <span>
                            {{ number_format($participation->extra_contribution, 0, ',', ' ') }}
                            FCFA
                        </span>
                    </div>
                @endif

                <div class="confirmation-amount-total">
                    <div class="confirmation-amount-row">
                        <span>Total attendu</span>

                        <span>
                            {{ number_format($participation->total_expected, 0, ',', ' ') }}
                            FCFA
                        </span>
                    </div>
                </div>

            </div>

        </section>

        {{-- PROCHAINES ÉTAPES --}}
        <section class="confirmation-next">

            <h2>Et maintenant ?</h2>

            <div class="confirmation-next-step">
                <span class="confirmation-next-number">1</span>

                <p>
                    <strong>Conserve ta référence.</strong>
                    Elle permettra à l’équipe de retrouver ta participation.
                </p>
            </div>

            <div class="confirmation-next-step">
                <span class="confirmation-next-number">2</span>

                <p>
                    <strong>Attends la vérification.</strong>
                    L’équipe RDSA vérifiera le paiement et la preuve transmise.
                </p>
            </div>

            <div class="confirmation-next-step">
                <span class="confirmation-next-number">3</span>

                <p>
                    <strong>Confirmation définitive.</strong>
                    Une fois le paiement validé, les informations relatives
                    à tes billets pourront être communiquées.
                </p>
            </div>

        </section>

        {{-- BOUTONS --}}
        <div class="confirmation-actions">

            <a
                href="{{ url('/') }}"
                class="confirmation-button confirmation-button-primary"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="m3 10 9-7 9 7"></path>
                    <path d="M5 9v12h14V9"></path>
                    <path d="M9 21v-7h6v7"></path>
                </svg>

                Retourner à l'accueil
            </a>

            <button
                type="button"
                class="confirmation-button confirmation-button-secondary"
                onclick="window.print()"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M6 9V3h12v6"></path>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <path d="M6 14h12v7H6z"></path>
                </svg>

                Imprimer le récapitulatif
            </button>

        </div>

        <footer class="confirmation-footer">
            <strong>RDSA - Réunion des Assoiffés de Prière &amp; d’Adoration</strong>
            <br>
            Merci pour ta confiance. Que le Seigneur te bénisse.
        </footer>

    </div>
</main>

<style>
    @media print {
        body {
            background: #fff !important;
        }

        .rdsa-confirmation {
            min-height: auto;
            padding: 0;
            background: #fff;
        }

        .confirmation-actions {
            display: none;
        }

        .confirmation-hero,
        .confirmation-summary,
        .confirmation-next,
        .confirmation-status {
            break-inside: avoid;
            box-shadow: none;
        }
    }
</style>

@endsection