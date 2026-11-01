<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer">
<title>{{ $ticket->reference }} — RDSA</title>
<style>
:root{color-scheme:dark;--page:#080d1a;--surface:#111c30;--ink:#f3f6fc;--muted:#b1bed2;--edge:#30415d;--status:#92dfb4}
*{box-sizing:border-box}body{margin:0;padding:36px 16px;background:var(--page);color:var(--ink);font-family:Arial,sans-serif;line-height:1.4}.ticket{max-width:620px;margin:auto;background:var(--surface);border:1px solid var(--edge);border-radius:28px;overflow:hidden;box-shadow:0 20px 60px #102d6912}.header{display:flex;align-items:center;justify-content:space-between;gap:24px;background:#102d69;color:white;padding:28px 36px;border-bottom:5px solid #d8b45a;print-color-adjust:exact}.logo{display:block;width:120px;height:auto;flex-shrink:0}.edition{text-align:right}.kicker{margin:0 0 8px;color:#e8cd91;font-size:13px;font-weight:bold;letter-spacing:2px}h1{margin:0;font-size:44px;line-height:1.1;overflow-wrap:anywhere}.content{padding:30px 36px;text-align:center}.name{font-size:28px;margin:0 0 18px;font-weight:bold;overflow-wrap:anywhere}.event{margin:0;font-size:21px;font-weight:bold}.venue{margin:7px 0 0;color:var(--muted);font-size:18px}.entry{margin-top:24px;border:1px solid #e1e7f0;border-radius:24px;padding:8px;background:white}.qr{display:block;width:400px;max-width:100%;height:auto;margin:0 auto}.reference{margin:18px 0 0;font-size:20px;font-weight:bold;letter-spacing:1px;overflow-wrap:anywhere}.footer{display:flex;justify-content:space-between;gap:16px;margin-top:16px;color:var(--muted);font-size:15px}.status{color:var(--status);font-weight:bold}.unavailable{padding:42px 16px;font-size:22px;font-weight:bold}.actions{max-width:620px;margin:22px auto;display:flex;gap:10px;flex-wrap:wrap}.button{flex:1;padding:14px 18px;border:1px solid var(--edge);border-radius:12px;background:var(--surface);color:var(--ink);font:inherit;text-align:center;text-decoration:none;cursor:pointer}.primary{background:#102d69;border-color:#102d69;color:white}@media(max-width:520px){body{padding:16px 12px}.header{padding:22px;gap:16px}.logo{width:94px}h1{font-size:34px}.kicker{font-size:11px;letter-spacing:1px}.content{padding:24px 20px}.name{font-size:25px}.event{font-size:19px}.venue{font-size:17px}.reference{font-size:17px;letter-spacing:.3px}.footer{font-size:14px}.actions{flex-direction:column}}@media print{:root{color-scheme:light;--page:#fff;--surface:#fff;--ink:#102d69;--muted:#52617a;--edge:#d9e1ec;--status:#236443}@page{size:A5;margin:8mm}body{padding:0;background:white}.ticket{box-shadow:none;max-width:100%;break-inside:avoid}.header{padding:20px 26px}.logo{width:90px}h1{font-size:36px}.content{padding:22px 26px}.qr{width:320px}.name{font-size:25px}.actions{display:none}}
.reservation-confirmation{max-width:620px;margin:0 auto 20px;padding:18px;border:1px solid #d6ad54;border-radius:14px;background:#201226;color:#fff}.header{border-bottom-color:#d6ad54}.primary{background:#102d69;border-color:#102d69}@media print{.reservation-confirmation{display:none}}
@media screen{.kicker{color:#e8cd91}.footer{flex-wrap:wrap}}
</style>
@vite(['resources/css/component-motion.css', 'resources/css/site-designs.css'])
</head>
@php($siteDesign = \App\Support\SiteDesign::resolve($ticket->participation->edition))
<body data-site-design="{{ $siteDesign['preset'] }}" data-design-active="{{ $siteDesign['active'] ? 'true' : 'false' }}" data-design-effect="{{ $siteDesign['effect'] }}" data-design-radius="{{ $siteDesign['radius'] }}" style="{{ $siteDesign['style'] }}">
@if(session('reservation_success'))<p class="reservation-confirmation" role="status">Réservation confirmée. Ton billet est prêt ; conserve-le ou télécharge son PDF.</p>@endif
<main class="ticket">
<header class="header">
@if($logo)<img class="logo" src="{{ $logo }}" alt="Logo RDSA">@endif
<div class="edition"><p class="kicker">BILLET D’ENTRÉE</p><h1>{{ $ticket->participation->edition->name }}</h1></div>
</header>
<div class="content">
<p class="name">{{ $ticket->participation->participant->full_name }}</p>
<p class="event">{{ $ticket->participation->edition->starts_at->locale('fr')->translatedFormat('d F Y') }} · {{ $ticket->participation->edition->starts_at->format('H:i') }}</p>
<p class="venue">{{ $ticket->participation->edition->venue }}</p>
<div class="entry">
@if($qrDataUri)<img class="qr" src="{{ $qrDataUri }}" alt="QR code personnel de contrôle du billet">
@else<p class="unavailable">{{ $ticket->status === 'used' ? 'Billet déjà utilisé' : 'Billet indisponible' }}</p>@endif
</div>
<p class="reference">{{ $ticket->reference }}</p>
<div class="footer"><span>1 personne · 1 entrée</span>@if($usable)<span class="status">Billet valide</span>@endif</div>
</div>
</main>
@if(!$download)
<div class="actions">
<a class="button primary" href="{{ request()->url() }}?download=1">Télécharger le PDF</a>
@if($usable)<a class="button" data-qr-download data-filename="{{ $ticket->reference }}-qr.png" href="{{ request()->url() }}?qr=1">Enregistrer le QR</a><a class="button" href="https://wa.me/?text={{ rawurlencode('Mon billet RDSA : ' . request()->url() . "\nPDF : " . request()->url() . '?download=1') }}" target="_blank" rel="noopener noreferrer">Partager sur WhatsApp</a>@endif
<button class="button" type="button" onclick="window.print()">Imprimer</button>
<a class="button" href="{{ route('home') }}">Accueil</a>
</div>
@endif
@if(!$download)
@vite(['resources/css/component-motion.css', 'resources/js/interactions.js'])
@endif
</body></html>
