<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><style>
@page{margin:16px}body{margin:0;font-family:"DejaVu Sans",sans-serif;color:#102d69;line-height:1.35}.ticket{border:1px solid #d9e1ec;border-radius:18px;overflow:hidden}.header{width:100%;background:#102d69;color:white;border-bottom:4px solid #d6ad54;border-spacing:0}.header td{padding:18px 20px;vertical-align:middle}.logo{width:82px;height:auto}.edition{text-align:right}.kicker{font-size:8px;color:#e8cd91;letter-spacing:1px;margin:0 0 6px}h1{font-size:28px;line-height:1.1;margin:0}.content{text-align:center;padding:18px 20px}.name{font-size:22px;font-weight:bold;margin:0 0 12px}.event{font-size:16px;font-weight:bold;margin:0}.venue{font-size:14px;color:#52617a;margin:6px 0 0}.entry{margin-top:14px;border:1px solid #e1e7f0;border-radius:18px;padding:2px}.qr{width:260px;height:260px}.reference{font-size:14px;font-weight:bold;letter-spacing:.6px;margin:12px 0 0}.footer{width:100%;margin-top:10px;font-size:10px;color:#52617a}.status{color:#236443;text-align:right}.unavailable{font-size:18px;font-weight:bold;padding:65px 0}
</style></head><body><div class="ticket">
<table class="header"><tr><td style="width:85px">@if($logo)<img class="logo" src="{{ $logo }}" alt="Logo RDSA">@endif</td><td class="edition"><p class="kicker">BILLET D’ENTRÉE</p><h1>{{ $ticket->participation->edition->name }}</h1></td></tr></table>
<div class="content">
<p class="name">{{ $ticket->participation->participant->full_name }}</p>
<p class="event">{{ $ticket->participation->edition->starts_at->locale('fr')->translatedFormat('d F Y') }} · {{ $ticket->participation->edition->starts_at->format('H:i') }}</p>
<p class="venue">{{ $ticket->participation->edition->venue }}</p>
<div class="entry">@if($qrDataUri)<img class="qr" src="{{ $qrDataUri }}" alt="QR code">@else<p class="unavailable">{{ $ticket->status === 'used' ? 'Billet déjà utilisé' : 'Billet indisponible' }}</p>@endif</div>
<p class="reference">{{ $ticket->reference }}</p>
<table class="footer"><tr><td>1 personne · 1 entrée</td><td class="status">{{ $usable ? 'Billet valide' : '' }}</td></tr></table>
</div></div></body></html>
