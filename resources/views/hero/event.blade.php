<div class="ref-event-details">
<div><i class="bi bi-calendar-event" aria-hidden="true"></i><span><small>LE RENDEZ-VOUS</small><strong>{{ $edition?->starts_at?->locale('fr')->translatedFormat('d F Y') ?? 'Date à confirmer' }}</strong><span>{{ $edition?->starts_at?->format('H\hi') ?? 'Heure à confirmer' }}</span></span></div>
<div><i class="bi bi-geo-alt" aria-hidden="true"></i><span><small>LE LIEU</small><strong>{{ $edition?->venue ?? 'Lieu à confirmer' }}</strong>@if($edition?->venue_details)<span>{{ $edition->venue_details }}</span>@endif</span></div>
</div>
