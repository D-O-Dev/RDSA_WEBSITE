@extends('admin.layout')
@section('admin-title', 'Codes de secours')
@section('admin-content')
<section class="card"><h3>Double authentification activée</h3><p>Conserve ces codes dans un endroit privé. Chacun permet une seule connexion si tu perds ton application. Ils ne seront plus affichés après avoir quitté cette page.</p><div class="kv">@foreach($codes as $code)<code>{{ $code }}</code>@endforeach</div><div class="act"><a class="btn" download="rdsa-codes-de-secours.txt" href="data:text/plain;charset=utf-8,{{ rawurlencode(implode("\n", $codes)) }}">Télécharger mes codes</a><a class="btn" href="{{ route('admin.account.edit') }}">J’ai conservé mes codes</a></div></section>
@endsection
