<form class="bar" method="GET">
<div class="srch"><i class="bi bi-search"></i><input name="q" value="{{ request('q') }}" maxlength="150" placeholder="Nom, téléphone ou référence" aria-label="Rechercher"></div>
<select class="sel" name="status" aria-label="Statut"><option value="">Tous les statuts</option>
@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach
</select>
<select class="sel" name="edition" aria-label="Édition"><option value="">Toutes les éditions</option>
@foreach($editions as $edition)<option value="{{ $edition->id }}" @selected((string) request('edition') === (string) $edition->id)>{{ $edition->name }}</option>@endforeach
</select>
<button class="btn pri" type="submit"><i class="bi bi-search"></i>Filtrer</button>
<a class="btn" href="{{ url()->current() }}">Réinitialiser</a>
</form>
@if($errors->any())<div class="lock" role="alert">{{ $errors->first() }}</div>@endif
