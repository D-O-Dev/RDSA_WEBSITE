@if(in_array($reservation->status, ['cancelled', 'rejected']))
<a class="btn sm" href="{{ route('admin.participations.index', ['q' => $reservation->reference, 'delete' => $reservation->id]) }}"><i class="bi bi-trash3" aria-hidden="true"></i>Supprimer définitivement</a>
@endif
