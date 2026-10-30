@extends('admin.layout')
@section('admin-title', 'Tableau de bord')
@section('admin-content')
<style>.dashboard-metrics{grid-template-columns:repeat(4,minmax(0,1fr))}.dashboard-metrics .kpi b{overflow-wrap:anywhere}.dashboard-row{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--line)}.dashboard-row b{overflow-wrap:anywhere}.dashboard-row .meta{text-align:right}@media(max-width:1100px){.dashboard-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.dashboard-metrics{grid-template-columns:1fr}}</style>
<form class="bar" method="GET"><select class="sel" name="edition" aria-label="Édition"><option value="">Toutes les éditions</option>@foreach($editions as $edition)<option value="{{ $edition->id }}" @selected((string) request('edition') === (string) $edition->id)>{{ $edition->name }}</option>@endforeach</select><button class="btn" type="submit">Filtrer</button></form>
@if(!$activeEdition)<div class="lock">Aucune édition ouverte aux inscriptions. <a class="btn sm" href="{{ route('admin.index') }}">Gérer les éditions</a></div>@endif
<div class="grid dashboard-metrics">
@foreach($metrics as [$icon, $label, $value])
<div class="card kpi"><i class="bi bi-{{ $icon }}"></i><span>{{ $label }}</span><b>{{ number_format($value, 0, ',', ' ') }}</b></div>
@endforeach
</div>
<div class="grid" style="margin-top:18px">
<section class="card"><h3>Dernières réservations</h3>
@forelse($recentParticipations as $participation)
<div class="dashboard-row"><div><b>{{ $participation->participant->full_name }}</b><div class="hint">{{ $participation->reference }}</div></div><div class="meta"><div class="hint">{{ $participation->edition->name }}</div>@include('admin.partials.status', ['status' => $participation->status])</div></div>
@empty<div class="empty">Aucune participation enregistrée.</div>@endforelse
<div class="act"><a class="btn sm" href="{{ route('admin.participations.index', ['edition' => request('edition')]) }}">Toutes les réservations</a></div></section>
</div>
@endsection