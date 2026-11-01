@extends('admin.layout')
@section('admin-title', 'Contrôle des billets')
@section('admin-content')
<div class="grid g2">
<section class="card"><h3><i class="bi bi-qr-code-scan"></i>Contrôler un billet</h3>
<form id="check-in-form" data-inspect="{{ route('admin.check-in.inspect') }}" data-confirm="{{ route('admin.check-in.confirm') }}">
@csrf
<div class="f"><label for="check-edition">Édition contrôlée</label><select class="in" name="edition_id" id="check-edition" required><option value="">Choisir l’édition</option>@foreach($editions as $edition)<option value="{{ $edition->id }}">{{ $edition->name }} · {{ $edition->starts_at->format('d/m/Y') }}</option>@endforeach</select></div>
<div class="act"><button class="btn" type="button" id="camera-start" disabled><i class="bi bi-camera"></i>Démarrer la caméra</button><button class="btn" type="button" id="camera-stop" hidden>Arrêter la caméra</button></div>
<div class="f"><label for="camera-choice">Caméra à utiliser</label><select class="in" id="camera-choice"><option value="">Arrière (automatique)</option></select></div>
<div class="f"><label for="qr-photo">Ou lire une photo du QR</label><input class="in" type="file" id="qr-photo" accept="image/*"><p class="hint">Choisis une photo nette ou une capture du QR. La photo est lue sur cet appareil et n’est pas envoyée au serveur.</p></div>
<div id="qr-reader" style="width:100%;margin-top:16px;overflow:hidden;border-radius:12px"></div>
<p class="hint" id="camera-message" role="status">La caméra lit le QR code. Tu peux aussi saisir la référence ci-dessous.</p>
<div class="f"><label for="check-code">Référence du billet</label><input class="in" name="code" id="check-code" placeholder="RDSA-TK-…" maxlength="512" autocomplete="off" spellcheck="false" required></div>
<div class="act"><button class="btn pri" type="submit" id="check-inspect" disabled>Vérifier le billet</button></div>
</form>
<noscript><p class="hint">Active JavaScript pour utiliser le contrôle des billets.</p></noscript>
</section>
<section class="card"><h3>Résultat du contrôle</h3>
<div id="check-result" role="status" aria-live="polite"><p class="hint">Choisis l’édition puis scanne un billet ou saisis sa référence.</p></div>
<div class="act" style="margin-top:18px"><button class="btn pri" id="check-confirm" type="button" hidden>Confirmer l’entrée</button><button class="btn" id="check-next" type="button">Billet suivant</button></div>
<p class="hint" style="margin-top:16px">La vérification seule n’enregistre aucune entrée. Chaque confirmation est contrôlée à nouveau par le serveur.</p>
</section>
</div>
@endsection
