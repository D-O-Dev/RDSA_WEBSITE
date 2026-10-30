@extends('admin.layout')
@section('admin-title', 'Messages')
@section('admin-content')
@if(session('status'))<div class="lock" role="status">{{ session('status') }}</div>@endif
<form class="bar" method="GET"><div class="srch"><i class="bi bi-search"></i><input name="q" value="{{ request('q') }}" maxlength="150" placeholder="Nom, sujet, e-mail ou référence" aria-label="Rechercher"></div><select class="sel" name="status" aria-label="Statut"><option value="">Tous les messages</option>@foreach(['new' => 'Nouveaux', 'read' => 'Lus', 'done' => 'Répondus', 'archived' => 'Archivés'] as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select><button class="btn pri" type="submit">Rechercher</button><a class="btn" href="{{ route('admin.messages.index') }}">Réinitialiser</a></form>
<div class="inbox-list">
@forelse($messages as $message)
<a class="card inbox-row {{ $message->status === 'new' ? 'unread' : '' }}" href="{{ route('admin.messages.show', $message) }}"><div class="inbox-avatar"><i class="bi bi-chat-left-text"></i></div><div class="inbox-main"><div class="inbox-heading"><b>{{ $message->subject }}</b><span class="bd {{ $message->status === 'new' ? 'wa' : 'mu' }}">{{ ['new' => 'Nouveau', 'read' => 'Lu', 'done' => 'Répondu', 'archived' => 'Archivé'][$message->status] }}</span></div><p>{{ \Illuminate\Support\Str::limit($message->body, 110) }}</p><small>{{ $message->name }} · {{ $message->reference }}</small></div><time>{{ $message->updated_at->format('d/m H:i') }}</time></a>
@empty<div class="card empty"><i class="bi bi-chat-dots"></i>Aucun message ne correspond à ta recherche.</div>@endforelse
</div>@include('admin.partials.pagination', ['results' => $messages])
@endsection
