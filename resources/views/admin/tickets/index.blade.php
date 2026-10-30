@extends('admin.layout')
@section('admin-title', 'Billets')
@section('admin-content')
@include('admin.partials.filters', ['statuses' => ['valid' => 'Valides', 'used' => 'Utilisés', 'cancelled' => 'Annulés', 'pending' => 'En attente']])
<div class="tw"><table><thead><tr><th>Billet</th><th>Participant</th><th>Édition</th><th>Émis le</th><th>Statut</th><th></th></tr></thead>
<tbody>@forelse($tickets as $ticket)
<tr><td><b>{{ $ticket->reference }}</b></td><td>{{ $ticket->participation->participant->full_name }}<div class="hint">{{ $ticket->participation->participant->phone }}</div></td><td>{{ $ticket->participation->edition->name }}</td><td>{{ $ticket->issued_at?->format('d/m/Y H:i') }}</td><td>@include('admin.partials.status', ['status' => $ticket->status])</td><td><a class="btn sm" href="{{ route('admin.tickets.show', $ticket) }}">Voir le billet</a></td></tr>
@empty<tr><td colspan="6"><div class="empty"><i class="bi bi-ticket-perforated"></i>Aucun billet ne correspond à ta recherche.</div></td></tr>@endforelse
</tbody></table>@include('admin.partials.pagination', ['results' => $tickets])</div>
@endsection
