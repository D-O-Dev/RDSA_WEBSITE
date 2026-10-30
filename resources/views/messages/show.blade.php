@extends('layouts.app')
@section('title', 'Ma conversation — RDSA')
@section('content')
<main class="rdsa-conversation"><div class="container"><div class="conversation-wrap">
<a class="conversation-home" href="{{ route('home') }}"><i class="bi bi-arrow-left"></i>Retour à l’accueil</a>
<header><span class="section-kicker">ESPACE PRIVÉ · {{ $message->reference }}</span><h1>{{ $message->subject }}</h1><p>Conserve ce lien personnel pour lire les réponses. La conversation se met à jour automatiquement.</p></header>
<div data-conversation data-thread-url="{{ $message->privateUrl() }}" data-version="{{ $message->status }}:{{ $message->replies->last()?->id ?? 0 }}" aria-live="polite">@include('messages.thread')</div>
<form class="conversation-form" data-thread-form action="{{ route('messages.reply', ['reference' => $message->reference, 'token' => $message->access_token]) }}" method="POST">@csrf
<label for="followup">Ajouter une précision</label><textarea class="form-control" id="followup" name="body" rows="3" minlength="2" maxlength="5000" required></textarea><div data-form-feedback role="status"></div><button class="questions-submit" type="submit">Envoyer <i class="bi bi-send"></i></button></form>
</div></div></main>
@endsection
