<form method="POST" action="{{ route('tickets.lookup') }}" class="ticket-lookup-form" data-ticket-lookup>
@csrf
<div class="form-heading"><h2 id="ticket-dialog-title">Retrouver mon billet</h2><p>Référence + téléphone ou code privé : retrouve ton billet.</p></div>
<label for="ticket-reference">Référence</label><input class="form-control" id="ticket-reference" name="reference" value="{{ old('reference') }}" placeholder="RDSA-TK-… ou RDSA-…" maxlength="50" required autocomplete="off" autocapitalize="characters" spellcheck="false">
<label for="ticket-access">Téléphone ou code privé</label><input class="form-control" id="ticket-access" name="access_code" type="password" maxlength="100" required autocomplete="off">
<div class="form-feedback" data-lookup-feedback role="status" aria-live="polite">{{ $errors->first('reference') ?: $errors->first('access_code') }}</div><button class="primary-action" type="submit">Afficher mon billet <i class="bi bi-arrow-up-right"></i></button>
</form>
