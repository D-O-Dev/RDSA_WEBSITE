@extends('admin.layout')
@section('admin-title', 'Présences')
@section('admin-content')
<form class="bar" method="GET">
    <div class="srch"><i class="bi bi-search"></i><input name="q" value="{{ request('q') }}" maxlength="150" placeholder="Nom ou référence du billet" aria-label="Rechercher une présence"></div>
    <select class="sel" name="edition" aria-label="Édition">
        <option value="">Toutes les éditions</option>
        @foreach($editions as $edition)
        <option value="{{ $edition->id }}" @selected((string) request('edition') === (string) $edition->id)>{{ $edition->name }}</option>
        @endforeach
    </select>
    <button class="btn pri" type="submit">Filtrer</button>
    <a class="btn" href="{{ route('admin.attendances.index') }}">Réinitialiser</a>
    @can('manage-team')<a class="btn" download href="{{ route('admin.exports', ['kind' => 'attendances'] + request()->only(['q', 'edition'])) }}"><i class="bi bi-download" aria-hidden="true"></i>Exporter CSV</a>@endcan
</form>
@if($errors->any())
<div class="lock" role="alert">{{ $errors->first() }}</div>
@endif
<div class="tw attendance-table">
    <table>
        <thead><tr><th>Billet</th><th>Participant</th><th>Édition</th><th>Entrée enregistrée</th><th>Validée par</th></tr></thead>
        <tbody>
        @forelse($attendances as $attendance)
            <tr>
                <td>
                    @can('manage-team')
                    <a class="attendance-ticket-link" href="{{ route('admin.tickets.show', $attendance->ticket) }}" aria-label="Consulter le billet {{ $attendance->ticket->reference }}"><i class="bi bi-ticket-perforated" aria-hidden="true"></i><span><strong>{{ $attendance->ticket->reference }}</strong><small>Consulter le billet <i class="bi bi-arrow-up-right" aria-hidden="true"></i></small></span></a>
                    @else
                    <strong class="attendance-person">{{ $attendance->ticket->reference }}</strong>
                    @endcan
                </td>
                <td><strong class="attendance-person">{{ $attendance->ticket->participation->participant->full_name }}</strong><div class="hint">{{ $attendance->ticket->participation->participant->phone }}</div></td>
                <td>{{ $attendance->ticket->participation->edition->name }}</td>
                <td><time datetime="{{ $attendance->checked_in_at->toIso8601String() }}"><span>{{ $attendance->checked_in_at->format('d/m/Y') }}</span><span class="hint attendance-time">{{ $attendance->checked_in_at->format('H:i:s') }}</span></time></td>
                <td>{{ $attendance->checker?->name ?? 'Compte supprimé' }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><i class="bi bi-door-open"></i>Aucune entrée enregistrée.</div></td></tr>
        @endforelse
        </tbody>
    </table>
    @include('admin.partials.pagination', ['results' => $attendances])
</div>
@endsection
