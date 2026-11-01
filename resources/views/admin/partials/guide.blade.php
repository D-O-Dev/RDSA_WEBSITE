@php
$guideTopics = collect(config('admin_guide'))->filter(fn($topic) => ($topic['audience'] ?? 'admin') === 'all' || (($topic['audience'] ?? 'admin') === 'super' ? auth()->user()->can('manage-sensitive-data') : (($topic['audience'] ?? 'admin') === 'admin' ? auth()->user()->is_admin : !auth()->user()->is_admin)));
@endphp
<dialog class="admin-commands admin-guide" id="rdsa-admin-guide" aria-labelledby="admin-guide-title" data-guide-user="{{ auth()->id() }}">
    <div class="command-heading"><div><span class="guide-kicker">Un coup de main, à chaque étape</span><h2 id="admin-guide-title">Guide de l’administration</h2></div><button class="ib" type="button" data-dialog-close aria-label="Fermer le guide"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
    <label class="command-label" for="admin-guide-topic">Quelle rubrique veux-tu découvrir ?</label>
    <select class="sel" id="admin-guide-topic">@foreach($guideTopics as $key => $topic)<option value="{{ $key }}">{{ $topic['title'] }}</option>@endforeach</select>
    <div class="guide-content" data-guide-content aria-live="polite"></div>
    <div class="guide-pagination"><button class="btn sm" type="button" data-guide-previous>Précédent</button><span class="hint" data-guide-progress></span><button class="btn sm" type="button" data-guide-next>Suivant</button></div>
    <div class="guide-footer"><p class="hint">Tu peux rouvrir ce guide à tout moment depuis le menu.</p><button class="btn" type="button" data-dialog-close>Compris</button></div>
    @foreach($guideTopics as $key => $topic)
    <template data-guide-template="{{ $key }}" @isset($topic['match']) data-guide-match="{{ $topic['match'] }}" data-guide-path="{{ parse_url(route($topic['route']), PHP_URL_PATH) }}" @endisset>
        <span class="guide-symbol" aria-hidden="true"><i class="bi {{ $topic['icon'] }}"></i></span><h3>{{ $topic['title'] }}</h3><p class="guide-summary">{{ $topic['summary'] }}</p><ol>@foreach($topic['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
        @isset($topic['route'])<a class="btn" data-guide-go href="{{ route($topic['route']) }}">Ouvrir cette rubrique <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>@endisset
    </template>
    @endforeach
</dialog>
