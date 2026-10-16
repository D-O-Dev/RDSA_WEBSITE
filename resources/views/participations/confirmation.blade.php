@extends('layouts.app')

@section('title', 'Confirmation de participation — RDSA')

@section('content')
    <main class="container py-5">
        <h1>Participation enregistrée</h1>

        <p>Ta demande de participation a bien été enregistrée.</p>

        <h2>Récapitulatif</h2>

        <p>
            <strong>Référence :</strong>
            {{ $participation->reference }}
        </p>

        <p>
            <strong>Participant :</strong>
            {{ $participation->participant->full_name }}
        </p>

        <p>
            <strong>Édition :</strong>
            {{ $participation->edition->name }}
        </p>

        <p>
            <strong>Nombre de places :</strong>
            {{ $participation->quantity }}
        </p>

        <p>
            <strong>Prix unitaire :</strong>
            {{ number_format($participation->unit_price, 0, ',', ' ') }} FCFA
        </p>

        <p>
            <strong>Participation :</strong>
            {{ number_format($participation->base_amount, 0, ',', ' ') }} FCFA
        </p>

        <p>
            <strong>Contribution supplémentaire :</strong>
            {{ number_format($participation->extra_contribution, 0, ',', ' ') }} FCFA
        </p>

        <p>
            <strong>Total à payer :</strong>
            {{ number_format($participation->total_expected, 0, ',', ' ') }} FCFA
        </p>

        <p>
            <strong>Statut :</strong>
            {{ $participation->status }}
        </p>

        <p>
            La prochaine étape sera de procéder au paiement Wave et de transmettre la preuve.
        </p>

        <a href="{{ url('/') }}">Retour à l'accueil</a>
    </main>
@endsection