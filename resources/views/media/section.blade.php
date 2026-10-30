<section id="medias" class="rdsa-media rdsa-moments" aria-labelledby="moments-title">
<div class="container">
<div class="media-heading"><span class="section-kicker">{{ $siteContent['media_kicker'] }}</span><h2 id="moments-title">@php($heading = \App\Models\SiteContent::heading($siteContent['media_heading'])){{ $heading[0] }} <span>{{ $heading[1] }}</span></h2></div>

@if($mediaItems->isNotEmpty())
<button type="button" id="media-open" class="rdsa-album-box" aria-haspopup="dialog" aria-controls="media-dialog">
<span class="rdsa-album-cover"><img src="{{ $mediaItems->first()['thumbnail'] }}" alt="" loading="lazy"><span class="rdsa-album-cover-icon"><i class="bi bi-images"></i></span></span>
<span class="rdsa-album-invitation"><span class="section-kicker">{{ $siteContent['media_album_label'] }}</span><strong>{!! nl2br(e($siteContent['media_invitation'])) !!}</strong><span>{{ $mediaItems->where('kind', 'photo')->count() }} photos · {{ $mediaItems->where('kind', 'video')->count() }} vidéos</span><span class="rdsa-album-cta">Ouvrir la galerie <i class="bi bi-arrow-up-right"></i></span></span>
</button>
@else<p class="moments-empty">Les souvenirs de cette édition seront publiés ici.</p>@endif
<script type="application/json" id="media-data">@json($mediaItems)</script>
</div>
</section>
<dialog class="moments-dialog" id="media-dialog" aria-labelledby="media-dialog-title">
<div class="moments-dialog-top"><div><span id="media-dialog-album">{{ $siteContent['media_album_label'] }}</span><h3 id="media-dialog-title">La galerie</h3></div><button type="button" id="media-close" data-dialog-close aria-label="Fermer la galerie" autofocus><i class="bi bi-x-lg"></i></button></div>
<div class="moments-toolbar">
<div class="moments-tabs" role="group" aria-label="Type de média"><button type="button" class="is-active" data-media-kind="all" aria-pressed="true">Tout</button><button type="button" data-media-kind="photo" aria-pressed="false"><i class="bi bi-camera"></i> Photos</button><button type="button" data-media-kind="video" aria-pressed="false"><i class="bi bi-play-circle"></i> Vidéos</button></div>
<label class="moments-album"><span>Édition</span><select id="media-album"><option value="">Toutes</option>@foreach($mediaItems->pluck('album')->unique() as $album)<option value="{{ $album }}">{{ $album }}</option>@endforeach</select></label>
</div>
<div class="rdsa-album-viewer"><div class="rdsa-album-main"><div class="moments-stage" id="media-stage"></div><div class="media-source" id="media-source" hidden><span>Si la lecture est limitée par la plateforme, ouvre la vidéo d’origine.</span><a id="media-original" target="_blank" rel="noopener noreferrer"></a></div></div><div class="rdsa-album-filmstrip" id="media-filmstrip" role="group" aria-label="Choisir un souvenir"></div></div>
<p class="moments-empty" id="media-empty" hidden>Aucun média dans cette sélection.</p>
<div class="moments-dialog-bottom"><button type="button" id="media-prev" aria-label="Média précédent"><i class="bi bi-arrow-left"></i></button><span id="media-position" role="status" aria-live="polite"></span><button type="button" id="media-next" aria-label="Média suivant"><i class="bi bi-arrow-right"></i></button></div>
</dialog>
