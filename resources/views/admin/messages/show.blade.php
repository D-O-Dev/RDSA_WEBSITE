@extends('admin.layout')
@section('admin-title', 'Conversation')
@section('admin-content')
<div class="bar"><a class="btn" href="{{ route('admin.messages.index') }}"><i class="bi bi-arrow-left"></i>Messages</a><form action="{{ route('admin.messages.archive', $message) }}" method="POST">@csrf<button class="btn" type="submit">Archiver</button></form></div>
<section class="card conversation-admin">
<h3>{{ $message->subject }}</h3><p class="hint">{{ $message->name }} · {{ $message->reference }}</p><div class="act" style="justify-content:flex-start">@if($message->email)<a class="btn sm" href="mailto:{{ $message->email }}">{{ $message->email }}</a>@endif @if($message->phone)<span class="bd mu">{{ $message->phone }}</span>@endif</div>
<div data-conversation data-thread-url="{{ route('admin.messages.show', $message) }}" data-version="{{ $message->status }}:{{ $message->replies->last()?->id ?? 0 }}" aria-live="polite">@include('messages.thread')</div>
<form data-thread-form action="{{ route('admin.messages.reply', $message) }}" method="POST">@csrf<div class="f"><label for="reply-body">Ta réponse</label><textarea class="in" name="body" id="reply-body" rows="4" minlength="2" maxlength="5000" required></textarea></div><p class="hint">La réponse apparaît dans la conversation privée du visiteur.</p><div data-form-feedback role="status"></div><div class="act"><button class="btn pri" type="submit"><i class="bi bi-send"></i>Publier la réponse</button></div></form>
</section>
@endsection
