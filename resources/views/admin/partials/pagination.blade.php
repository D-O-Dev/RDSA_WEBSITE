<div class="pgn">
<span class="hint">{{ $results->total() }} résultat(s) · Page {{ $results->currentPage() }} / {{ $results->lastPage() }}</span>
<div class="act">
@if($results->previousPageUrl())<a class="btn sm" href="{{ $results->previousPageUrl() }}">Précédent</a>@endif
@if($results->hasMorePages())<a class="btn sm" href="{{ $results->nextPageUrl() }}">Suivant</a>@endif
</div></div>
