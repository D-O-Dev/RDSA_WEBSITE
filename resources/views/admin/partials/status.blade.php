@php
$labels = ['valid' => ['ok', 'Valide'], 'used' => ['mu', 'Utilisé'], 'pending' => ['wa', 'En attente'], 'verified' => ['ok', 'Vérifié'], 'confirmed' => ['ok', 'Confirmée'], 'rejected' => ['er', 'Rejeté'], 'cancelled' => ['mu', 'Annulée']];
[$color, $label] = $labels[$status] ?? ['mu', $status];
@endphp
<span class="bd {{ $color }}">{{ $label }}</span>
