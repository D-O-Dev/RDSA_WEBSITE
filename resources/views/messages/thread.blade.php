@php($statusLabels = ['new' => 'En attente de lecture', 'read' => 'L’équipe a lu ton message', 'done' => 'Réponse disponible', 'archived' => 'Conversation archivée'])
<div class="conversation-state">{{ $statusLabels[$message->status] ?? 'En cours' }}</div>
<article class="conversation-bubble visitor"><div class="conversation-author">{{ $message->name }}<time>{{ $message->created_at->format('d/m/Y H:i') }}</time></div><p>{{ $message->body }}</p></article>
@foreach($message->replies as $reply)
<article class="conversation-bubble {{ $reply->author_type === 'admin' ? 'staff' : 'visitor' }}"><div class="conversation-author">{{ $reply->author_type === 'admin' ? 'Équipe RDSA' : $message->name }}<time>{{ $reply->created_at->format('d/m/Y H:i') }}</time></div><p>{{ $reply->body }}</p></article>
@endforeach
