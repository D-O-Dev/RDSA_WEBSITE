@extends('admin.layout')
@section('admin-title', 'Réservations')
@section('admin-content')
@if(session('status'))
<div class="lock" role="status">{{ session('status') }}</div>
@endif
@include('admin.partials.filters', ['statuses' => ['pending' => 'En attente', 'confirmed' => 'Confirmées', 'rejected' => 'Rejetées', 'cancelled' => 'Annulées']])
<div class="tw">
    <table>
        <thead>
            <tr><th>Référence</th><th>Participant</th><th>Édition</th><th>Statut</th><th>Vérification / Billet</th></tr>
        </thead>
        <tbody>
        @forelse($participations as $participation)
            <tr>
                <td>
                    <b>{{ $participation->reference }}</b>
                    <div class="hint">{{ $participation->created_at->format('d/m/Y H:i') }}</div>
                </td>
                <td>
                    {{ $participation->participant->full_name }}
                    <div class="hint">{{ $participation->participant->phone }}</div>
                    <div class="hint">{{ $participation->has_attended_before ? 'Déjà présent : ' . $editions->whereIn('id', $participation->previous_edition_ids ?? [])->pluck('name')->join(', ') : 'Première participation' }}</div>
                </td>
                <td>{{ $participation->edition->name }}</td>
                <td>@include('admin.partials.status', ['status' => $participation->status])</td>
                <td>
                    <div class="act reservation-actions">
                    @forelse($participation->tickets as $ticket)
                        <a class="btn sm" href="{{ route('admin.tickets.show', $ticket) }}">Voir le billet</a>
                    @empty
                        @if($participation->status === 'pending')
                        <form method="POST" action="{{ route('admin.participations.review', $participation) }}">
                            @csrf
                            <input type="hidden" name="decision" value="approve">
                            <button class="btn pri sm" type="submit"><i class="bi bi-shield-check"></i>Valider et émettre</button>
                        </form>
                        <details class="reservation-rejection">
                            <summary class="btn sm">Refuser la demande</summary>
                            <form method="POST" action="{{ route('admin.participations.review', $participation) }}">
                                @csrf
                                <input type="hidden" name="decision" value="reject">
                                <label class="hint" for="rejection-{{ $participation->id }}">Motif du refus</label>
                                <input class="in" id="rejection-{{ $participation->id }}" name="reason" maxlength="1000" required>
                                <button class="btn sm" type="submit">Confirmer le refus</button>
                            </form>
                        </details>
                        @else
                        <span class="hint">{{ $participation->status === 'rejected' ? 'Demande refusée' : 'Aucun billet émis' }}</span>
                        @endif
                    @endforelse
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><i class="bi bi-inbox"></i>Aucune participation ne correspond à ta recherche.</div></td></tr>
        @endforelse
        </tbody>
    </table>
    @include('admin.partials.pagination', ['results' => $participations])
</div>
@endsection
