@extends('admin.layout')
@section('admin-title', 'Réservations')
@section('admin-content')
@if(session('status'))
<div class="lock" role="status">{{ session('status') }}</div>
@endif
@include('admin.partials.filters', ['statuses' => ['pending' => 'En attente', 'confirmed' => 'Confirmées', 'rejected' => 'Rejetées', 'cancelled' => 'Annulées']])
<div class="bar"><a class="btn" href="{{ route('admin.participations.create') }}"><i class="bi bi-person-plus" aria-hidden="true"></i>Inscrire une personne</a></div>
@include('admin.partials.bulk', ['bulkKind' => 'participations'])
<div class="tw">
    <table>
        <thead>
            <tr><th>Référence</th><th>Participant</th><th>Édition</th><th>Statut</th><th>Vérification / Billet</th></tr>
        </thead>
        <tbody>
        @forelse($participations as $participation)
            <tr>
                <td>
                    <label><input type="checkbox" name="ids[]" value="{{ $participation->id }}" form="bulk-participations" aria-label="Sélectionner {{ $participation->reference }}"> <b>{{ $participation->reference }}</b></label>
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
                    <a class="btn sm" href="{{ route('admin.participations.manage', $participation) }}"><i class="bi bi-pencil-square" aria-hidden="true"></i>Gérer / corriger</a>
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
                    @if(in_array($participation->status, ['pending', 'confirmed']) && !$participation->tickets->contains('status', 'used'))
                    <details class="reservation-rejection"><summary class="btn sm">Annuler la réservation</summary><form method="POST" action="{{ route('admin.participations.cancel', $participation) }}">@csrf<label class="hint" for="cancel-{{ $participation->id }}">Motif de l’annulation</label><input class="in" id="cancel-{{ $participation->id }}" name="reason" maxlength="1000" required><p class="hint">Le billet sera invalidé. L’historique reste conservé.</p><button class="btn sm bad" type="submit">Confirmer l’annulation</button></form></details>
                    @endif
                    @include('admin.partials.delete-reservation', ['reservation' => $participation])
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

@section('admin-modal')
@if($deleting)
<div class="ov on"><div class="md" role="dialog" aria-modal="true" aria-labelledby="reservation-delete-title">
<div class="media-editor-heading"><h3 id="reservation-delete-title">Supprimer cette réservation</h3><a class="btn media-editor-close" href="{{ route('admin.participations.index', ['q' => $deleting->reference]) }}" aria-label="Fermer"><i class="bi bi-x-lg" aria-hidden="true"></i></a></div>
<p class="hint">La réservation de {{ $deleting->participant->full_name }} et son billet seront effacés. Cette action est irréversible. Un document déjà téléchargé ne peut pas être rappelé.</p>
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.participations.destroy', $deleting) }}">@csrf @method('DELETE')
<div class="f"><label for="delete-reference">Recopie {{ $deleting->reference }}</label><input class="in" id="delete-reference" name="confirmation_reference" value="{{ old('confirmation_reference') }}" required autocomplete="off"></div>
<div class="f"><label for="delete-password">Ton mot de passe administrateur</label><input class="in" id="delete-password" name="current_password" type="password" required autocomplete="current-password"></div>
<div class="act"><a class="btn" href="{{ route('admin.participations.index', ['q' => $deleting->reference]) }}">Annuler</a><button class="btn bad" type="submit">Supprimer définitivement</button></div></form>
</div></div>
@endif
@endsection
