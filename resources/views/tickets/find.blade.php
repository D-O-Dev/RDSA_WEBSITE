@extends('layouts.app')
@section('title', 'Retrouver mon billet — RDSA')
@section('content')
<main class="ticket-find-page"><div><a class="text-link" href="{{ route('home') }}">← Retour à l’accueil</a>@include('tickets.lookup-form')</div></main>
@endsection
